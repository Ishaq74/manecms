<?php

namespace Database\Factories;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Tenancy\Models\Tenant;
use Database\Factories\Concerns\BypassesRowSecurity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AuditEvent>
 */
class AuditEventFactory extends Factory
{
    /** @use BypassesRowSecurity<AuditEvent> */
    use BypassesRowSecurity;

    protected $model = AuditEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'action' => 'tenancy.workspace.renamed',
            'before' => ['name' => fake()->words(2, true)],
            'after' => ['name' => fake()->words(2, true)],
            'source' => 'http',
            'correlation_id' => (string) Str::ulid(),
            'occurred_at' => now(),
        ];
    }
}
