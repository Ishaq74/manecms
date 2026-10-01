<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Tenancy\Models\Workspace;
use App\Models\User;

/**
 * Picks the workspace a user lands on after signing in.
 *
 * The workspace used most recently wins. Otherwise the oldest active workspace
 * of the oldest membership is used, so the choice never depends on row order.
 */
final class ResolveEntryWorkspace
{
    public function __invoke(User $user): ?Workspace
    {
        $remembered = Workspace::query()
            ->select('workspaces.*')
            ->join('tenant_members', 'tenant_members.last_workspace_id', '=', 'workspaces.id')
            ->where('tenant_members.user_id', $user->id)
            ->whereNull('workspaces.archived_at')
            ->orderByDesc('tenant_members.updated_at')
            ->orderByDesc('tenant_members.id')
            ->first();

        return $remembered ?? Workspace::query()
            ->select('workspaces.*')
            ->join('tenant_members', 'tenant_members.tenant_id', '=', 'workspaces.tenant_id')
            ->where('tenant_members.user_id', $user->id)
            ->whereNull('workspaces.archived_at')
            ->orderBy('tenant_members.created_at')
            ->orderBy('tenant_members.id')
            ->orderBy('workspaces.created_at')
            ->orderBy('workspaces.id')
            ->first();
    }
}
