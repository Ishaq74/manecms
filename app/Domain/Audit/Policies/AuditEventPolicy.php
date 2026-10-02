<?php

namespace App\Domain\Audit\Policies;

use App\Domain\Audit\Permissions\AuditPermission;
use App\Domain\Authorization\PolicyEngine;
use App\Models\User;

final readonly class AuditEventPolicy
{
    public function __construct(private PolicyEngine $engine) {}

    public function viewAny(User $user): bool
    {
        return $this->engine->decide($user, AuditPermission::EventView)->allows();
    }
}
