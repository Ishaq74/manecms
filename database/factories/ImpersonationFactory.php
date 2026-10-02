<?php

namespace Database\Factories;

use App\Domain\Platform\Models\Impersonation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Impersonation>
 */
class ImpersonationFactory extends Factory
{
    protected $model = Impersonation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'operator_id' => User::factory(),
            'user_id' => User::factory(),
            'reason' => 'Ticket #'.fake()->numberBetween(1000, 9999).' : '.fake()->sentence(),
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ];
    }
}
