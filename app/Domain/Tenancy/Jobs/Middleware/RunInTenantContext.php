<?php

namespace App\Domain\Tenancy\Jobs\Middleware;

use App\Domain\Tenancy\Database\TenantDatabaseContext;
use App\Domain\Tenancy\Exceptions\TenantContextRequired;
use App\Domain\Tenancy\Exceptions\TenantSuspended;
use App\Domain\Tenancy\Jobs\TenantScopedJob;
use App\Domain\Tenancy\Models\Tenant;
use Closure;
use Illuminate\Support\Str;

/**
 * Runs a job inside the database scope of its tenant, then restores the worker's scope.
 * A job of a suspended tenant fails instead of running.
 */
final class RunInTenantContext
{
    /**
     * @param  Closure(object): mixed  $next
     */
    public function handle(object $job, Closure $next): mixed
    {
        if (! $job instanceof TenantScopedJob || ! Str::isUlid($job->tenantId())) {
            throw new TenantContextRequired;
        }

        return app(TenantDatabaseContext::class)->runAs($job->tenantId(), null, function () use ($job, $next): mixed {
            $tenant = Tenant::query()->find($job->tenantId());

            if ($tenant?->isSuspended() === true) {
                throw new TenantSuspended($tenant->suspension_reason);
            }

            return $next($job);
        });
    }
}
