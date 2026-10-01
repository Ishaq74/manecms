<?php

namespace Database\Factories;

use App\Domain\Tenancy\Enums\TenantRole;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantMember>
 */
class TenantMemberFactory extends Factory
{
    protected $model = TenantMember::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'role' => TenantRole::Member,
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (): array => ['role' => TenantRole::Owner]);
    }

    public function admin(): static
    {
        return $this->state(fn (): array => ['role' => TenantRole::Admin]);
    }

    public function member(): static
    {
        return $this->state(fn (): array => ['role' => TenantRole::Member]);
    }
}
