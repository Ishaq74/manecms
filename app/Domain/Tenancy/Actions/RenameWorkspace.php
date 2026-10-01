<?php

namespace App\Domain\Tenancy\Actions;

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

    public function __construct(private TenantContext $context) {}

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
            DB::transaction(fn (): bool => $workspace->update(['name' => $name]));
        } catch (UniqueConstraintViolationException) {
            throw $this->workspaceNameTaken();
        }

        return $workspace;
    }
}
