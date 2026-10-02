<?php

namespace Database\Factories;

use App\Domain\Authorization\Actions\ProvisionSystemRoles;
use App\Domain\Authorization\Enums\SystemRole;
use App\Domain\Authorization\Models\Role;
use App\Domain\Tenancy\Database\TenantDatabaseContext;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantMember;
use App\Models\User;
use Database\Factories\Concerns\BypassesRowSecurity;
use Illuminate\Database\Eloquent\Factories\Factory;
use InvalidArgumentException;

/**
 * @extends Factory<TenantMember>
 */
class TenantMemberFactory extends Factory
{
    /** @use BypassesRowSecurity<TenantMember> */
    use BypassesRowSecurity;

    protected $model = TenantMember::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'role_id' => fn (array $attributes): string => self::systemRoleId($attributes['tenant_id'], SystemRole::Member),
        ];
    }

    /**
     * `is_owner` is computed by a database trigger: copy it back onto the model.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (TenantMember $member): void {
            $isOwner = app(TenantDatabaseContext::class)->withoutRowSecurity(
                fn (): mixed => TenantMember::query()->whereKey($member->id)->value('is_owner'),
            );

            $member->forceFill(['is_owner' => (bool) $isOwner])->syncOriginal();
        });
    }

    public function owner(): static
    {
        return $this->withSystemRole(SystemRole::Owner);
    }

    public function admin(): static
    {
        return $this->withSystemRole(SystemRole::Admin);
    }

    public function member(): static
    {
        return $this->withSystemRole(SystemRole::Member);
    }

    public function withSystemRole(SystemRole $role): static
    {
        return $this->state(fn (): array => [
            'role_id' => fn (array $attributes): string => self::systemRoleId($attributes['tenant_id'], $role),
        ]);
    }

    public function withRole(Role $role): static
    {
        return $this->state(fn (): array => ['tenant_id' => $role->tenant_id, 'role_id' => $role->id]);
    }

    private static function systemRoleId(mixed $tenantId, SystemRole $role): string
    {
        $tenantId = is_string($tenantId) ? $tenantId : throw new InvalidArgumentException('A tenant id is required.');

        return app(TenantDatabaseContext::class)->withoutRowSecurity(function () use ($tenantId, $role): string {
            $roleId = Role::query()->where('tenant_id', $tenantId)->where('system_key', $role)->value('id');

            return is_string($roleId) ? $roleId : app(ProvisionSystemRoles::class)($tenantId)[$role->value]->id;
        });
    }
}
