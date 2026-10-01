<?php

namespace Database\Seeders;

use App\Domain\Tenancy\Enums\TenantRole;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The ManeCMS spaces used to explore the application locally.
 *
 * Idempotent: running it again leaves the same tenants, workspaces and
 * memberships in place instead of adding copies.
 */
class TenancySeeder extends Seeder
{
    public function run(User $owner, User $guest): void
    {
        $maneCms = $this->tenantOwnedBy($owner, 'ManeCMS');
        $this->workspaces($maneCms, active: ['ManeCMS', 'Site vitrine', 'Marketing'], archived: ['Archives 2025']);
        $this->membership($maneCms, $owner, TenantRole::Owner);
        $this->membership($maneCms, $guest, TenantRole::Member);

        $atlas = $this->tenantOwnedBy($guest, 'Studio Atlas');
        $this->workspaces($atlas, active: ['Studio Atlas', 'Clients'], archived: []);
        $this->membership($atlas, $guest, TenantRole::Owner);
        $this->membership($atlas, $owner, TenantRole::Admin);
    }

    private function tenantOwnedBy(User $owner, string $name): Tenant
    {
        return Tenant::query()
            ->where('name', $name)
            ->whereHas('members', fn ($query) => $query->where('user_id', $owner->id)->where('role', TenantRole::Owner))
            ->first()
            ?? Tenant::query()->create(['name' => $name]);
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

    private function membership(Tenant $tenant, User $user, TenantRole $role): void
    {
        $tenant->members()->firstOrCreate(['user_id' => $user->id], ['role' => $role]);
    }
}
