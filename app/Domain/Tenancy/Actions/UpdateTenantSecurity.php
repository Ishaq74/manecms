<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Audit\AuditLog;
use App\Domain\Authorization\Errors\AuthorizationDenied;
use App\Domain\Authorization\PolicyEngine;
use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Permissions\TenancyPermission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Requires two-factor authentication from the members who hold privileged permissions.
 */
final readonly class UpdateTenantSecurity
{
    public function __construct(
        private TenantContext $context,
        private PolicyEngine $engine,
        private AuditLog $audit,
    ) {}

    /**
     * @throws AuthorizationDenied
     * @throws ValidationException
     */
    public function __invoke(User $actor, bool $requireMfa): Tenant
    {
        $tenant = $this->context->tenant();

        $this->engine->authorize($actor, TenancyPermission::TenantSecurity);

        if ($tenant->require_mfa === $requireMfa) {
            return $tenant;
        }

        // Turning it on without 2FA would lock the owner out of the setting itself.
        if ($requireMfa && ! $actor->hasEnabledTwoFactorAuthentication()) {
            throw ValidationException::withMessages(['requireMfa' => __('Enable two-factor authentication on your account first.')]);
        }

        DB::transaction(function () use ($tenant, $requireMfa, $actor): void {
            $tenant->require_mfa = $requireMfa;
            $tenant->save();

            $this->audit->record('tenancy.tenant.security_updated', $tenant, ['require_mfa' => ! $requireMfa], ['require_mfa' => $requireMfa], actorId: $actor->id);
        });

        return $tenant;
    }
}
