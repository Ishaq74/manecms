<?php

namespace App\Domain\Audit\Policies;

use App\Domain\Tenancy\Context\TenantContext;
use App\Models\User;

final readonly class AuditEventPolicy
{
    public function __construct(private TenantContext $context) {}

    public function viewAny(User $user): bool
    {
        return $this->context->isInstalled()
            && $this->context->member()->user_id === $user->id
            && $this->context->role()->canManageTenant();
    }
}
