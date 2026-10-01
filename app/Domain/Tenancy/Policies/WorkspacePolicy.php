<?php

namespace App\Domain\Tenancy\Policies;

use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Models\Workspace;
use App\Models\User;

final readonly class WorkspacePolicy
{
    public function __construct(private TenantContext $context) {}

    public function create(User $user): bool
    {
        return $this->managesWorkspacesAs($user);
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $this->managesWorkspacesAs($user)
            && $workspace->tenant_id === $this->context->member()->tenant_id;
    }

    public function archive(User $user, Workspace $workspace): bool
    {
        return $this->update($user, $workspace);
    }

    private function managesWorkspacesAs(User $user): bool
    {
        return $this->context->isInstalled()
            && $this->context->member()->user_id === $user->id
            && $this->context->role()->canManageWorkspaces();
    }
}
