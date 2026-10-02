<?php

namespace App\Domain\Tenancy\Policies;

use App\Domain\Authorization\PolicyEngine;
use App\Domain\Tenancy\Models\Workspace;
use App\Domain\Tenancy\Permissions\TenancyPermission;
use App\Models\User;

final readonly class WorkspacePolicy
{
    public function __construct(private PolicyEngine $engine) {}

    public function create(User $user): bool
    {
        return $this->engine->decide($user, TenancyPermission::WorkspaceCreate)->allows();
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $this->engine->decide($user, TenancyPermission::WorkspaceUpdate, $workspace)->allows();
    }

    public function archive(User $user, Workspace $workspace): bool
    {
        return $this->engine->decide($user, TenancyPermission::WorkspaceArchive, $workspace)->allows();
    }
}
