<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Audit\AuditLog;
use App\Domain\Tenancy\Actions\Concerns\ValidatesNames;
use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Models\Workspace;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class RenameWorkspace
{
    use ValidatesNames;

    public function __construct(
        private TenantContext $context,
        private AuditLog $audit,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws ModelNotFoundException<Workspace>
     * @throws ValidationException
     */
    public function __invoke(User $user, string $workspaceId, string $name): Workspace
    {
        $tenant = $this->context->tenant();
        $workspace = $tenant->workspaces()->findOrFail($workspaceId);

        Gate::forUser($user)->authorize('update', $workspace);

        $name = $this->validatedName($name);
        $this->ensureWorkspaceNameIsFree($tenant, $name, $workspace->id);

        try {
            $before = $workspace->name;

            DB::transaction(function () use ($workspace, $name, $before, $user): void {
                $workspace->update(['name' => $name]);
                $this->audit->record('tenancy.workspace.renamed', $workspace, ['name' => $before], ['name' => $name], actorId: $user->id);
            });
        } catch (UniqueConstraintViolationException) {
            throw $this->workspaceNameTaken();
        }

        return $workspace;
    }
}
