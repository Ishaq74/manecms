<?php

namespace App\Domain\Platform\Actions;

use App\Domain\Audit\AuditLog;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Suspends or reactivates a tenant through `platform_set_tenant_suspension`, the only
 * cross-tenant write of the back-office; the function re-checks that the caller is an operator.
 */
final readonly class SetTenantSuspension
{
    public function __construct(private AuditLog $audit) {}

    /**
     * @throws ValidationException
     */
    public function suspend(User $operator, string $tenantId, string $reason): void
    {
        $reason = Str::squish($reason);

        Validator::make(['reason' => $reason], ['reason' => ['required', 'string', 'min:10', 'max:500']])->validate();

        $this->apply($operator, $tenantId, $reason);
    }

    public function reactivate(User $operator, string $tenantId): void
    {
        $this->apply($operator, $tenantId, null);
    }

    private function apply(User $operator, string $tenantId, ?string $reason): void
    {
        abort_unless($operator->isPlatformOperator() && Str::isUlid($tenantId), 404);

        DB::transaction(function () use ($operator, $tenantId, $reason): void {
            $row = (array) DB::selectOne('select platform_set_tenant_suspension(?, ?) as found', [$tenantId, $reason]);

            if (($row['found'] ?? false) !== true) {
                throw new NotFoundHttpException;
            }

            $this->audit->record(
                $reason === null ? 'platform.tenant.reactivated' : 'platform.tenant.suspended',
                (new Tenant)->forceFill(['id' => $tenantId]),
                after: $reason === null ? [] : ['reason' => $reason],
                actorId: $operator->id,
                platform: true,
            );
        });
    }
}
