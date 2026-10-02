<?php

namespace Database\Factories;

use App\Domain\Authorization\Enums\SystemRole;
use App\Domain\Authorization\Models\Role;
use App\Domain\Tenancy\Database\TenantDatabaseContext;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantInvitation;
use Database\Factories\Concerns\BypassesRowSecurity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * @extends Factory<TenantInvitation>
 */
class TenantInvitationFactory extends Factory
{
    /** @use BypassesRowSecurity<TenantInvitation> */
    use BypassesRowSecurity;

    protected $model = TenantInvitation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'email' => Str::lower(fake()->unique()->safeEmail()),
            'role_id' => fn (array $attributes): string => self::memberRoleId($attributes['tenant_id']),
            'token_hash' => TenantInvitation::hashToken(Str::random(48)),
            'expires_at' => now()->addDays(TenantInvitation::VALID_DAYS),
        ];
    }

    public function withToken(string $token): static
    {
        return $this->state(fn (): array => ['token_hash' => TenantInvitation::hashToken($token)]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['expires_at' => now()->subDay()]);
    }

    public function revoked(): static
    {
        return $this->state(fn (): array => ['revoked_at' => now()]);
    }

    public function accepted(): static
    {
        return $this->state(fn (): array => ['accepted_at' => now()]);
    }

    private static function memberRoleId(mixed $tenantId): string
    {
        $tenantId = is_string($tenantId) ? $tenantId : throw new InvalidArgumentException('A tenant id is required.');

        return app(TenantDatabaseContext::class)->withoutRowSecurity(
            fn (): string => Role::query()->where('tenant_id', $tenantId)->where('system_key', SystemRole::Member)->sole()->id,
        );
    }
}
