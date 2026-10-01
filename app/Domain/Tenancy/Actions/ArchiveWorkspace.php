<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Models\Workspace;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class ArchiveWorkspace
{
    public function __construct(private TenantContext $context) {}

    /**
     * @throws AuthorizationException
     * @throws ModelNotFoundException<Workspace>
     * @throws ValidationException
     */
    public function __invoke(User $user, string $workspaceId): Workspace
    {
        $tenant = $this->context->tenant();
        $workspace = $tenant->workspaces()->active()->findOrFail($workspaceId);

        Gate::forUser($user)->authorize('archive', $workspace);

        DB::transaction(function () use ($tenant, $workspace): void {
            // Locking the tenant row serialises concurrent archives of its last two workspaces.
            Tenant::query()->whereKey($tenant->id)->lockForUpdate()->sole();

            $activeCount = $tenant->workspaces()->active()->count();

            if ($activeCount <= 1) {
                throw ValidationException::withMessages([
                    'workspace' => __('The last active workspace cannot be archived.'),
                ]);
            }

            $workspace->archived_at = now();
            $workspace->save();

            TenantMember::query()
                ->where('last_workspace_id', $workspace->id)
                ->update(['last_workspace_id' => null]);
        });

        return $workspace;
    }
}
