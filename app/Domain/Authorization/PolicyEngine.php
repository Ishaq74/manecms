<?php

namespace App\Domain\Authorization;

use App\Domain\Audit\AuditLog;
use App\Domain\Authorization\Enums\Capability;
use App\Domain\Authorization\Enums\DecisionType;
use App\Domain\Authorization\Enums\DenialReason;
use App\Domain\Authorization\Enums\SystemRole;
use App\Domain\Authorization\Errors\ApprovalRequired;
use App\Domain\Authorization\Errors\AuthorizationDenied;
use App\Domain\Authorization\Models\Role;
use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Models\Workspace;
use App\Models\User;

/**
 * The single place that decides what a member may do (todo/todo.md §28.1).
 *
 * The steps run in a fixed order and the first that applies wins: unknown
 * permission, missing context, archived tenant, workspace restriction,
 * capability, RBAC, MFA, escalation guards, segregation of duties, approval,
 * then constraints.
 */
final readonly class PolicyEngine
{
    public const string CONSTRAINT_AUDIT = 'audit';

    public function __construct(
        private TenantContext $context,
        private PermissionRegistry $registry,
        private AuditLog $audit,
    ) {}

    /**
     * @param  TenantMember|null  $targetMember  The member the action changes, for escalation guards.
     * @param  Role|null  $assignedRole  The role the action hands out.
     * @param  int|null  $resourceCreatorId  Who created the resource, for segregation of duties.
     */
    public function decide(
        User $actor,
        Permission $permission,
        ?Workspace $workspace = null,
        ?TenantMember $targetMember = null,
        ?Role $assignedRole = null,
        ?int $resourceCreatorId = null,
    ): Decision {
        $denial = $this->scopeDenial($actor, $permission, $workspace)
            ?? $this->grantDenial($actor, $permission)
            ?? $this->escalationDenial($targetMember, $assignedRole);

        if ($denial !== null) {
            return Decision::deny($denial);
        }

        if ($permission->segregatesDuties() && $resourceCreatorId === $actor->id) {
            return Decision::deny(DenialReason::SegregationOfDuties);
        }

        if ($permission->requiresApproval()) {
            return Decision::requireApproval();
        }

        return $permission->capability() === Capability::Safe
            ? Decision::allow()
            : Decision::allowWithConstraints([self::CONSTRAINT_AUDIT]);
    }

    /**
     * Decide, then audit and throw unless the action may run now.
     *
     * Call it before opening the transaction of the action, so the audit of a
     * denial is not rolled back with it.
     *
     * @throws AuthorizationDenied
     * @throws ApprovalRequired
     */
    public function authorize(
        User $actor,
        Permission $permission,
        ?Workspace $workspace = null,
        ?TenantMember $targetMember = null,
        ?Role $assignedRole = null,
        ?int $resourceCreatorId = null,
    ): Decision {
        $decision = $this->decide($actor, $permission, $workspace, $targetMember, $assignedRole, $resourceCreatorId);

        if ($decision->type === DecisionType::Deny && $decision->reason !== null) {
            $this->audit->record(
                'authorization.denied',
                $targetMember ?? $assignedRole,
                after: array_filter([
                    'permission' => $permission->key(),
                    'reason' => $decision->reason->value,
                    'role' => $assignedRole?->key,
                ]),
                actorId: $actor->id,
                platform: ! $this->context->isInstalled(),
            );

            throw new AuthorizationDenied($decision->reason);
        }

        if ($decision->type === DecisionType::RequireApproval) {
            throw new ApprovalRequired($permission->key());
        }

        return $decision;
    }

    /**
     * Whether the space requires 2FA from this member and they have not enabled it:
     * only members holding a privileged permission are concerned.
     */
    public function requiresMfaEnrolment(User $actor): bool
    {
        if (! $this->context->isInstalled() || ! $this->context->tenant()->require_mfa || $actor->hasEnabledTwoFactorAuthentication()) {
            return false;
        }

        $privileged = array_keys(array_filter(
            $this->registry->all(),
            fn (Permission $permission): bool => $permission->capability() === Capability::Privileged,
        ));

        return array_intersect($privileged, $this->context->member()->role->permissionKeys()) !== [];
    }

    private function scopeDenial(User $actor, Permission $permission, ?Workspace $workspace): ?DenialReason
    {
        if ($this->registry->find($permission->key()) === null) {
            return DenialReason::UnknownPermission;
        }

        if (! $this->context->isInstalled()) {
            return DenialReason::NoTenantContext;
        }

        $member = $this->context->member();

        if ($member->user_id !== $actor->id) {
            return DenialReason::ActorMismatch;
        }

        if ($this->context->tenant()->isArchived()) {
            return DenialReason::TenantArchived;
        }

        $workspace ??= $this->context->workspace();

        if ($workspace->tenant_id !== $member->tenant_id || ! $member->allowsWorkspace($workspace->id)) {
            return DenialReason::WorkspaceRestricted;
        }

        return null;
    }

    private function grantDenial(User $actor, Permission $permission): ?DenialReason
    {
        $capability = $permission->capability();

        if (! $capability->isGrantable()) {
            return DenialReason::NotGrantable;
        }

        if (! $this->context->member()->role->grants($permission->key())) {
            return DenialReason::MissingPermission;
        }

        if ($capability === Capability::Privileged
            && $this->context->tenant()->require_mfa
            && ! $actor->hasEnabledTwoFactorAuthentication()) {
            return DenialReason::MfaRequired;
        }

        return null;
    }

    private function escalationDenial(?TenantMember $targetMember, ?Role $assignedRole): ?DenialReason
    {
        $actor = $this->context->member();

        if ($targetMember !== null) {
            if ($targetMember->tenant_id !== $actor->tenant_id) {
                return DenialReason::ForeignTarget;
            }

            if ($targetMember->is_owner) {
                return DenialReason::OwnerProtected;
            }

            if ($targetMember->id === $actor->id) {
                return DenialReason::SelfTarget;
            }

            if (! $actor->is_owner && $targetMember->role->isSystemRole(SystemRole::Admin)) {
                return DenialReason::PeerAdmin;
            }

            if (! $this->actorCovers($actor, $targetMember->role)) {
                return DenialReason::RoleExceedsActor;
            }
        }

        if ($assignedRole !== null) {
            if ($assignedRole->tenant_id !== $actor->tenant_id) {
                return DenialReason::ForeignTarget;
            }

            if ($assignedRole->isSystemRole(SystemRole::Owner)) {
                return DenialReason::OwnerRoleNotAssignable;
            }

            if (! $this->actorCovers($actor, $assignedRole)) {
                return DenialReason::RoleExceedsActor;
            }
        }

        return null;
    }

    /**
     * Below the owner, nobody hands out or manages a role holding permissions they lack themselves.
     */
    private function actorCovers(TenantMember $actor, Role $role): bool
    {
        return $actor->is_owner || array_diff($role->permissionKeys(), $actor->role->permissionKeys()) === [];
    }
}
