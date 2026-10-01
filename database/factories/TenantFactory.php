<?php

namespace Database\Factories;

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
}
