<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Audit\AuditLog;
use App\Domain\Authorization\Errors\AuthorizationDenied;
use App\Domain\Authorization\PolicyEngine;
use App\Domain\Tenancy\Actions\Concerns\ValidatesNames;
use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Permissions\TenancyPermission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RenameTenant
{
    use ValidatesNames;

    public function __construct(
        private TenantContext $context,
        private AuditLog $audit,
        private PolicyEngine $engine,
    ) {}

    /**
     * @throws AuthorizationDenied
     * @throws ValidationException
     */
    public function __invoke(User $user, string $name): Tenant
    {
        $tenant = $this->context->tenant();

        $this->engine->authorize($user, TenancyPermission::TenantUpdate);

        $name = $this->validatedName($name);
        $before = $tenant->name;

        DB::transaction(function () use ($tenant, $name, $before, $user): void {
            $tenant->update(['name' => $name]);
            $this->audit->record('tenancy.tenant.renamed', $tenant, ['name' => $before], ['name' => $name], actorId: $user->id);
        });

        return $tenant;
    }
}
