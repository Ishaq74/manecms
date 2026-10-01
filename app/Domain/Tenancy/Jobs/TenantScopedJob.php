<?php

namespace App\Domain\Tenancy\Jobs;

/**
 * A queued job whose work belongs to exactly one tenant.
 */
interface TenantScopedJob
{
    public function tenantId(): string;
}
