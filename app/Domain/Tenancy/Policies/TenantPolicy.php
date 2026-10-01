<?php

namespace App\Domain\Tenancy\Policies;

use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;

final readonly class TenantPolicy
{
    public function __construct(private TenantContext $context) {}

    public function update(User $user, Tenant $tenant): bool
    {
        return $this->context->isInstalled()
            && $this->context->member()->user_id === $user->id
            && $this->context->member()->tenant_id === $tenant->id
            && $this->context->role()->canManageTenant();
    }
}
