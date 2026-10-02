<?php

namespace App\Domain\Tenancy\Policies;

use App\Domain\Authorization\PolicyEngine;
use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Permissions\TenancyPermission;
use App\Models\User;

final readonly class TenantPolicy
{
    public function __construct(
        private PolicyEngine $engine,
        private TenantContext $context,
    ) {}

    public function update(User $user, Tenant $tenant): bool
    {
        return $this->engine->decide($user, TenancyPermission::TenantUpdate)->allows()
            && $this->context->member()->tenant_id === $tenant->id;
    }
}
