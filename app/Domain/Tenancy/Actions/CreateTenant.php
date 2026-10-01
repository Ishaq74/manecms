<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Tenancy\Actions\Concerns\ValidatesNames;
use App\Domain\Tenancy\Database\TenantDatabaseContext;
use App\Domain\Tenancy\Enums\TenantRole;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\Workspace;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Creates a tenant with its first workspace and makes the user its owner.
 */
final class CreateTenant
{
    use ValidatesNames;

    public function __construct(private readonly TenantDatabaseContext $database) {}

    private const int MAX_PER_HOUR = 5;

    /**
     * @throws ValidationException
     */
    public function __invoke(User $user, string $name): Workspace
    {
        $rateLimiterKey = 'create-tenant:'.$user->id;

        if (RateLimiter::tooManyAttempts($rateLimiterKey, self::MAX_PER_HOUR)) {
            throw ValidationException::withMessages([
                'name' => __('You have created too many spaces. Try again in :minutes minutes.', [
                    'minutes' => (int) ceil(RateLimiter::availableIn($rateLimiterKey) / 60),
                ]),
            ]);
        }

        $name = $this->validatedName($name);

        $tenant = new Tenant(['name' => $name]);
        $tenant->id = $tenant->newUniqueId();

        // Row level security only accepts rows of the current tenant, so the new tenant becomes it.
        $workspace = $this->database->runAs($tenant->id, $user->id, fn (): Workspace => DB::transaction(function () use ($tenant, $user, $name): Workspace {
            $tenant->save();
            $workspace = $tenant->workspaces()->create(['name' => $name]);

            $tenant->members()->create([
                'user_id' => $user->id,
                'role' => TenantRole::Owner,
                'last_workspace_id' => $workspace->id,
            ]);

            return $workspace;
        }));

        RateLimiter::hit($rateLimiterKey, 3600);

        return $workspace;
    }
}
