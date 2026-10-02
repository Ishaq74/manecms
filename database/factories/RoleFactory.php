<?php

namespace Database\Factories;

use App\Domain\Authorization\Models\Role;
use App\Domain\Tenancy\Database\TenantDatabaseContext;
use App\Domain\Tenancy\Models\Tenant;
use Database\Factories\Concerns\BypassesRowSecurity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Custom roles; system roles come with every tenant.
 *
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /** @use BypassesRowSecurity<Role> */
    use BypassesRowSecurity;

    protected $model = Role::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->jobTitle());

        return [
            'tenant_id' => Tenant::factory(),
            'key' => Str::slug($name),
            'name' => $name,
        ];
    }

    /**
     * @param  list<string>  $permissionKeys
     */
    public function withPermissions(array $permissionKeys): static
    {
        return $this->afterCreating(function (Role $role) use ($permissionKeys): void {
            app(TenantDatabaseContext::class)->withoutRowSecurity(fn () => $role->syncPermissions($permissionKeys));
        });
    }
}
