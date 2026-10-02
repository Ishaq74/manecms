<?php

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Audit\Permissions\AuditPermission;
use App\Domain\Authorization\Actions\SyncPermissions;
use App\Domain\Authorization\Enums\DecisionType;
use App\Domain\Authorization\Enums\DenialReason;
use App\Domain\Authorization\Enums\SystemRole;
use App\Domain\Authorization\Errors\ApprovalRequired;
use App\Domain\Authorization\Errors\AuthorizationDenied;
use App\Domain\Authorization\Models\Role;
use App\Domain\Authorization\PolicyEngine;
use App\Domain\Tenancy\Actions\UpdateMemberRole;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Models\Workspace;
use App\Domain\Tenancy\Permissions\TenancyPermission;
use App\Models\User;
use Tests\Feature\Authorization\Fixtures\TestPermission;

function engine(): PolicyEngine
{
    return app(PolicyEngine::class);
}

function registerTestPermissions(): void
{
    config(['authorization.permissions' => [...config('authorization.permissions'), TestPermission::class]]);
    asOwner(fn () => app(SyncPermissions::class)());
}

it('allows a safe permission without constraints and audits guarded ones', function (): void {
    [$user, $workspace, $member] = joinWorkspace(SystemRole::Admin);
    enterWorkspace($member, $workspace);

    expect(engine()->decide($user, TenancyPermission::MemberView)->type)->toBe(DecisionType::Allow);

    $guarded = engine()->decide($user, TenancyPermission::MemberInvite);

    expect($guarded->type)->toBe(DecisionType::AllowWithConstraints)
        ->and($guarded->constraints)->toBe([PolicyEngine::CONSTRAINT_AUDIT]);
});

it('denies a permission that is not registered', function (): void {
    [$user, $workspace, $member] = joinWorkspace();
    enterWorkspace($member, $workspace);

    expect(engine()->decide($user, TestPermission::Read)->reason)->toBe(DenialReason::UnknownPermission);
});

it('denies outside a tenant context and for another user than the member', function (): void {
    [$user, $workspace, $member] = joinWorkspace();

    expect(engine()->decide($user, TenancyPermission::MemberView)->reason)->toBe(DenialReason::NoTenantContext);

    enterWorkspace($member, $workspace);

    expect(engine()->decide(User::factory()->create(), TenancyPermission::MemberView)->reason)->toBe(DenialReason::ActorMismatch);
});

it('denies everything in an archived tenant', function (): void {
    [$user, $workspace, $member] = joinWorkspace();
    asOwner(fn () => $member->tenant->forceFill(['archived_at' => now()])->save());
    enterWorkspace($member, $workspace);

    expect(engine()->decide($user, TenancyPermission::MemberView)->reason)->toBe(DenialReason::TenantArchived);
});

it('denies a workspace the member is restricted from', function (): void {
    [$user, $workspace, $member] = joinWorkspace(SystemRole::Admin);
    $other = Workspace::factory()->for($member->tenant)->create();
    asOwner(fn () => $member->restrictedWorkspaces()->attach($workspace->id, ['tenant_id' => $member->tenant_id]));
    enterWorkspace($member, $workspace);

    expect(engine()->decide($user, TenancyPermission::WorkspaceUpdate)->allows())->toBeTrue()
        ->and(engine()->decide($user, TenancyPermission::WorkspaceUpdate, $other)->reason)->toBe(DenialReason::WorkspaceRestricted);
});

it('never grants a code-only permission, even to the owner', function (): void {
    registerTestPermissions();
    [$user, $workspace, $member] = joinWorkspace();
    enterWorkspace($member, $workspace);

    expect(engine()->decide($user, TestPermission::Deploy)->reason)->toBe(DenialReason::NotGrantable);
});

it('denies a permission the role does not hold', function (): void {
    [$user, $workspace, $member] = joinWorkspace(SystemRole::Member);
    enterWorkspace($member, $workspace);

    expect(engine()->decide($user, AuditPermission::EventView)->reason)->toBe(DenialReason::MissingPermission);
});

it('requires two-factor authentication for privileged permissions when the space asks for it', function (): void {
    [$user, $workspace, $member] = joinWorkspace();
    asOwner(fn () => $member->tenant->forceFill(['require_mfa' => true])->save());
    enterWorkspace($member, $workspace);

    expect(engine()->decide($user, TenancyPermission::TenantTransfer)->reason)->toBe(DenialReason::MfaRequired)
        ->and(engine()->decide($user, TenancyPermission::MemberInvite)->allows())->toBeTrue();

    $user->forceFill(['two_factor_secret' => encrypt('secret'), 'two_factor_confirmed_at' => now()])->save();

    expect(engine()->decide($user, TenancyPermission::TenantTransfer)->allows())->toBeTrue();
});

it('separates duties: the creator of a resource cannot act on it', function (): void {
    registerTestPermissions();
    [$user, $workspace, $member] = joinWorkspace();
    enterWorkspace($member, $workspace);

    expect(engine()->decide($user, TestPermission::Publish, resourceCreatorId: $user->id)->reason)->toBe(DenialReason::SegregationOfDuties)
        ->and(engine()->decide($user, TestPermission::Publish, resourceCreatorId: $user->id + 1)->allows())->toBeTrue();
});

it('asks for an approval and refuses to run the action without it', function (): void {
    registerTestPermissions();
    [$user, $workspace, $member] = joinWorkspace();
    enterWorkspace($member, $workspace);

    expect(engine()->decide($user, TestPermission::Refund)->type)->toBe(DecisionType::RequireApproval)
        ->and(fn () => engine()->authorize($user, TestPermission::Refund))->toThrow(ApprovalRequired::class);
});

it('protects the owner, the actor and peer admins from member changes', function (): void {
    [$admin, $workspace, $adminMember] = joinWorkspace(SystemRole::Admin);
    $owner = TenantMember::factory()->owner()->for($adminMember->tenant)->create();
    $otherAdmin = TenantMember::factory()->admin()->for($adminMember->tenant)->create();
    $member = TenantMember::factory()->member()->for($adminMember->tenant)->create();
    enterWorkspace($adminMember, $workspace);

    $decide = fn (TenantMember $target): ?DenialReason => engine()->decide($admin, TenancyPermission::MemberRemove, targetMember: asOwner(fn () => $target->fresh(['role'])))->reason;

    expect($decide($owner))->toBe(DenialReason::OwnerProtected)
        ->and($decide($adminMember))->toBe(DenialReason::SelfTarget)
        ->and($decide($otherAdmin))->toBe(DenialReason::PeerAdmin)
        ->and($decide($member))->toBeNull();
});

it('lets nobody below the owner hand out a role richer than their own', function (): void {
    [$user, $workspace, $member] = joinWorkspace(SystemRole::Member);
    $inviter = asOwner(fn () => Role::factory()->for($member->tenant)->withPermissions([TenancyPermission::MemberView->key(), TenancyPermission::MemberInvite->key()])->create());
    asOwner(fn () => $member->update(['role_id' => $inviter->id]));
    $admin = asOwner(fn () => $member->tenant->roles()->where('system_key', SystemRole::Admin)->sole());
    $memberRole = asOwner(fn () => $member->tenant->roles()->where('system_key', SystemRole::Member)->sole());
    enterWorkspace(asOwner(fn () => $member->fresh()) ?? $member, $workspace);

    expect(engine()->decide($user, TenancyPermission::MemberInvite, assignedRole: $admin)->reason)->toBe(DenialReason::RoleExceedsActor)
        ->and(engine()->decide($user, TenancyPermission::MemberInvite, assignedRole: $memberRole)->allows())->toBeTrue();
});

it('denies an admin who promotes themselves to owner and audits the attempt', function (): void {
    [$admin, $workspace, $adminMember] = joinWorkspace(SystemRole::Admin);
    TenantMember::factory()->owner()->for($adminMember->tenant)->create();
    $ownerRole = asOwner(fn () => $adminMember->tenant->roles()->where('system_key', SystemRole::Owner)->sole());
    enterWorkspace($adminMember, $workspace);

    expect(fn () => app(UpdateMemberRole::class)($admin, $adminMember->id, $ownerRole->id))
        ->toThrow(fn (AuthorizationDenied $denied) => expect($denied->reason)->toBe(DenialReason::SelfTarget));

    $audit = asOwner(fn () => AuditEvent::query()->where('action', 'authorization.denied')->sole());

    expect($audit->actor_id)->toBe($admin->id)
        ->and($audit->tenant_id)->toBe($adminMember->tenant_id)
        ->and($audit->after)->toMatchArray(['permission' => TenancyPermission::MemberUpdateRole->key(), 'reason' => DenialReason::SelfTarget->value, 'role' => 'owner'])
        ->and(asOwner(fn () => $adminMember->fresh()?->is_owner))->toBeFalse();
});

it('keeps role checks out of policies, views and components', function (): void {
    $root = dirname(__DIR__, 3);
    $offenders = [];

    foreach (['app/Domain/Tenancy/Policies', 'app/Domain/Audit/Policies', 'resources/views', 'app/View', 'app/Livewire'] as $directory) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$directory}", FilesystemIterator::SKIP_DOTS)) as $file) {
            $source = (string) file_get_contents($file->getPathname());

            if (preg_match('/SystemRole|system_key|TenantRole|->is_owner\s*===|[\'"](owner|admin)[\'"]\s*===/', $source) === 1) {
                $offenders[] = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            }
        }
    }

    expect($offenders)->toBe([]);
});
