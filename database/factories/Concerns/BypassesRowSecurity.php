<?php

namespace Database\Factories\Concerns;

use App\Domain\Tenancy\Database\TenantDatabaseContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Persists factory models as the table owner, so fixtures can span tenants.
 *
 * Only tests and local seeders use factories; the application role cannot
 * switch to the owner, so this never widens access at runtime.
 *
 * @template TModel of Model
 */
trait BypassesRowSecurity
{
    /**
     * @param  Collection<int, TModel>  $results
     */
    protected function store(Collection $results): void
    {
        app(TenantDatabaseContext::class)->withoutRowSecurity(fn () => parent::store($results));
    }
}
