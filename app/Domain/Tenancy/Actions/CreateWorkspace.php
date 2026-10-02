<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Audit\AuditLog;
use App\Domain\Tenancy\Actions\Concerns\ValidatesNames;
use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Models\Workspace;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class CreateWorkspace
{
    use ValidatesNames;

    public function __construct(
        private TenantContext $context,
        private AuditLog $audit,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function __invoke(User $user, string $name): Workspace
    {
        Gate::forUser($user)->authorize('create', Workspace::class);

        $tenant = $this->context->tenant();
        $name = $this->validatedName($name);

        $this->ensureWorkspaceNameIsFree($tenant, $name);

        try {
            return DB::transaction(function () use ($tenant, $name, $user): Workspace {
                $workspace = $tenant->workspaces()->create(['name' => $name]);
                $this->audit->record('tenancy.workspace.created', $workspace, after: ['name' => $name], actorId: $user->id);

                return $workspace;
            });
        } catch (UniqueConstraintViolationException) {
            throw $this->workspaceNameTaken();
        }
    }
}
