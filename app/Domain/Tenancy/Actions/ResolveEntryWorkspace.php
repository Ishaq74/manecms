<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Tenancy\Database\TenantDatabaseContext;
use App\Domain\Tenancy\Models\Workspace;
use App\Models\User;

/**
 * Picks the workspace a user lands on after signing in.
 *
 * Only workspaces the user may open count (archived tenants and workspace
 * restrictions excluded). The workspace used most recently wins. Otherwise the oldest active workspace
 * of the oldest membership is used, so the choice never depends on row order.
 */
final class ResolveEntryWorkspace
{
    public function __construct(private readonly TenantDatabaseContext $database) {}

    public function __invoke(User $user): ?Workspace
    {
        return $this->database->runAs(null, $user->id, fn (): ?Workspace => $this->resolve($user));
    }

    private function resolve(User $user): ?Workspace
    {
        $remembered = Workspace::query()
            ->select('workspaces.*')
            ->join('tenant_members', 'tenant_members.last_workspace_id', '=', 'workspaces.id')
            ->where('tenant_members.user_id', $user->id)
            ->accessibleBy($user->id)
            ->orderByDesc('tenant_members.updated_at')
            ->orderByDesc('tenant_members.id')
            ->first();

        return $remembered ?? Workspace::query()
            ->select('workspaces.*')
            ->join('tenant_members', 'tenant_members.tenant_id', '=', 'workspaces.tenant_id')
            ->where('tenant_members.user_id', $user->id)
            ->accessibleBy($user->id)
            ->orderBy('tenant_members.created_at')
            ->orderBy('tenant_members.id')
            ->orderBy('workspaces.created_at')
            ->orderBy('workspaces.id')
            ->first();
    }
}
