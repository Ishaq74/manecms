<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Tenancy\Actions\Concerns\ValidatesNames;
use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class RenameTenant
{
    use ValidatesNames;

    public function __construct(private TenantContext $context) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function __invoke(User $user, string $name): Tenant
    {
        $tenant = $this->context->tenant();

        Gate::forUser($user)->authorize('update', $tenant);

        $tenant->update(['name' => $this->validatedName($name)]);

        return $tenant;
    }
}
