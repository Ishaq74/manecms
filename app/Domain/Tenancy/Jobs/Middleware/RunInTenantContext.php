<?php

namespace App\Domain\Tenancy\Jobs\Middleware;

use App\Domain\Tenancy\Database\TenantDatabaseContext;
use App\Domain\Tenancy\Exceptions\TenantContextRequired;
use App\Domain\Tenancy\Jobs\TenantScopedJob;
use Closure;
use Illuminate\Support\Str;

/**
 * Runs a job inside the database scope of its tenant, then restores the worker's scope.
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

        return app(TenantDatabaseContext::class)->runAs($job->tenantId(), null, fn (): mixed => $next($job));
    }
}
