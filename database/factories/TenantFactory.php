<?php

namespace Database\Factories;

use App\Domain\Authorization\Actions\ProvisionSystemRoles;
use App\Domain\Tenancy\Database\TenantDatabaseContext;
use App\Domain\Tenancy\Models\Tenant;
use Database\Factories\Concerns\BypassesRowSecurity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /** @use BypassesRowSecurity<Tenant> */
    use BypassesRowSecurity;

    protected $model = Tenant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Tenant $tenant): void {
            app(TenantDatabaseContext::class)->withoutRowSecurity(fn (): array => app(ProvisionSystemRoles::class)($tenant->id));
        });
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['archived_at' => now()]);
    }

    public function requiringMfa(): static
    {
        return $this->state(fn (): array => ['require_mfa' => true]);
    }
}
