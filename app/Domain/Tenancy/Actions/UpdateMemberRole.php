<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Audit\AuditLog;
use App\Domain\Authorization\Errors\AuthorizationDenied;
use App\Domain\Authorization\Models\Role;
use App\Domain\Authorization\PolicyEngine;
use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Permissions\TenancyPermission;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final readonly class UpdateMemberRole
{
    public function __construct(
        private TenantContext $context,
        private PolicyEngine $engine,
        private AuditLog $audit,
    ) {}

    /**
     * @throws AuthorizationDenied
     * @throws ModelNotFoundException<TenantMember|Role>
     */
    public function __invoke(User $actor, string $memberId, string $roleId): TenantMember
    {
        $tenant = $this->context->tenant();
        $member = $tenant->members()->with('role')->findOrFail($memberId);
        $role = $tenant->roles()->findOrFail($roleId);

        $this->engine->authorize($actor, TenancyPermission::MemberUpdateRole, targetMember: $member, assignedRole: $role);

        if ($member->role_id === $role->id) {
            return $member;
        }

        $before = $member->role->key;

        DB::transaction(function () use ($member, $role, $before, $actor): void {
            $member->role_id = $role->id;
            $member->save();

            $this->audit->record('tenancy.member.role_changed', $member, ['role' => $before], ['role' => $role->key], actorId: $actor->id);
        });

        return $member->setRelation('role', $role);
    }
}
