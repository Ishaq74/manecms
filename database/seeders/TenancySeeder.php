<?php

namespace Database\Seeders;

use App\Domain\Audit\Permissions\AuditPermission;
use App\Domain\Authorization\Actions\ProvisionSystemRoles;
use App\Domain\Authorization\Enums\SystemRole;
use App\Domain\Authorization\Models\Role;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantInvitation;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Permissions\TenancyPermission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The ManeCMS spaces used to explore the application locally: system and
 * custom roles, a member restricted to some workspaces and invitations in
 * every state.
 *
 * Idempotent: running it again leaves the same tenants, workspaces, roles,
 * memberships and invitations in place instead of adding copies.
 */
class TenancySeeder extends Seeder
{
    public const string DEMO_PASSWORD = 'Guest+123';

    public function run(User $owner, User $guest): void
    {
        $maneCms = $this->tenantOwnedBy($owner, 'ManeCMS');
        $this->workspaces($maneCms, active: ['ManeCMS', 'Site vitrine', 'Marketing'], archived: ['Archives 2025']);
        $this->membership($maneCms, $owner, $this->systemRole($maneCms, SystemRole::Owner));
        $this->membership($maneCms, $guest, $this->systemRole($maneCms, SystemRole::Member));

        $editor = $this->customRole($maneCms, 'Éditeur', [
            TenancyPermission::MemberView,
            TenancyPermission::WorkspaceCreate,
            TenancyPermission::WorkspaceUpdate,
        ]);
        $auditor = $this->customRole($maneCms, 'Auditeur', [TenancyPermission::MemberView, AuditPermission::EventView]);

        $this->membership($maneCms, $this->demoUser('Hugo Bernard', 'hugo@manecms.test'), $this->systemRole($maneCms, SystemRole::Admin));
        $camille = $this->membership($maneCms, $this->demoUser('Camille Martin', 'camille@manecms.test'), $editor);
        $this->restrict($camille, ['Site vitrine', 'Marketing']);
        $this->membership($maneCms, $this->demoUser('Inès Robert', 'ines@manecms.test'), $auditor);

        $this->invitation($maneCms, 'lea.dubois@exemple.test', $editor, $owner);
        $this->invitation($maneCms, 'paul.moreau@exemple.test', $this->systemRole($maneCms, SystemRole::Member), $owner, expired: true);
        $this->invitation($maneCms, 'ancien.prestataire@exemple.test', $auditor, $owner, revoked: true);

        $atlas = $this->tenantOwnedBy($guest, 'Studio Atlas');
        $this->workspaces($atlas, active: ['Studio Atlas', 'Clients'], archived: []);
        $this->membership($atlas, $guest, $this->systemRole($atlas, SystemRole::Owner));
        $this->membership($atlas, $owner, $this->systemRole($atlas, SystemRole::Admin));
    }

    private function tenantOwnedBy(User $owner, string $name): Tenant
    {
        $tenant = Tenant::query()
            ->where('name', $name)
            ->whereHas('members', fn ($query) => $query->where('user_id', $owner->id)->where('is_owner', true))
            ->first()
            ?? Tenant::query()->create(['name' => $name]);

        app(ProvisionSystemRoles::class)($tenant->id);

        return $tenant;
    }

    /**
     * @param  list<string>  $active
     * @param  list<string>  $archived
     */
    private function workspaces(Tenant $tenant, array $active, array $archived): void
    {
        foreach ($active as $name) {
            $tenant->workspaces()->firstOrCreate(['name' => $name]);
        }

        foreach ($archived as $name) {
            $workspace = $tenant->workspaces()->firstOrCreate(['name' => $name]);
            $workspace->archived_at ??= now();
            $workspace->save();
        }
    }

    private function systemRole(Tenant $tenant, SystemRole $role): Role
    {
        return $tenant->roles()->where('system_key', $role)->sole();
    }

    /**
     * @param  list<TenancyPermission|AuditPermission>  $permissions
     */
    private function customRole(Tenant $tenant, string $name, array $permissions): Role
    {
        $role = $tenant->roles()->firstOrCreate(['key' => Str::slug($name)], ['name' => $name]);
        $role->syncPermissions(array_map(fn (TenancyPermission|AuditPermission $permission): string => $permission->key(), $permissions));

        return $role;
    }

    private function membership(Tenant $tenant, User $user, Role $role): TenantMember
    {
        return $tenant->members()->firstOrCreate(['user_id' => $user->id], ['role_id' => $role->id]);
    }

    private function demoUser(string $name, string $email): User
    {
        return User::query()->firstOrCreate(['email' => $email], [
            'name' => $name,
            'password' => self::DEMO_PASSWORD,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @param  list<string>  $workspaceNames
     */
    private function restrict(TenantMember $member, array $workspaceNames): void
    {
        $workspaceIds = $member->tenant->workspaces()->whereIn('name', $workspaceNames)->pluck('id')->all();

        $member->restrictedWorkspaces()->syncWithPivotValues($workspaceIds, ['tenant_id' => $member->tenant_id]);
    }

    private function invitation(Tenant $tenant, string $email, Role $role, User $inviter, bool $expired = false, bool $revoked = false): void
    {
        if ($tenant->invitations()->where('email', $email)->exists()) {
            return;
        }

        $invitation = $tenant->invitations()->create([
            'email' => $email,
            'role_id' => $role->id,
            'token_hash' => TenantInvitation::hashToken(Str::random(48)),
            'invited_by' => $inviter->id,
            'expires_at' => $expired ? now()->subDays(2) : now()->addDays(TenantInvitation::VALID_DAYS),
        ]);

        if ($revoked) {
            $invitation->revoked_at = now()->subDay();
            $invitation->save();
        }
    }
}
