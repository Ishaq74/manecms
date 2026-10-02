<?php

namespace App\Domain\Authorization\Actions;

use App\Domain\Audit\AuditLog;
use App\Domain\Authorization\Errors\AuthorizationDenied;
use App\Domain\Authorization\Models\Role;
use App\Domain\Authorization\PolicyEngine;
use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Permissions\TenancyPermission;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CreateRole
{
    public function __construct(
        private TenantContext $context,
        private PolicyEngine $engine,
        private ValidateCustomRole $validate,
        private AuditLog $audit,
    ) {}

    /**
     * @param  list<string>  $permissionKeys
     *
     * @throws AuthorizationDenied
     * @throws ValidationException
     */
    public function __invoke(User $actor, string $name, array $permissionKeys): Role
    {
        $this->engine->authorize($actor, TenancyPermission::RoleManage);

        ['name' => $name, 'key' => $key, 'permissions' => $permissionKeys] = ($this->validate)($name, $permissionKeys);
        $tenant = $this->context->tenant();

        try {
            return DB::transaction(function () use ($tenant, $name, $key, $permissionKeys, $actor): Role {
                $role = $tenant->roles()->create(['key' => $key, 'name' => $name]);
                $role->syncPermissions($permissionKeys);

                $this->audit->record('authorization.role.created', $role, after: ['name' => $name, 'permissions' => $permissionKeys], actorId: $actor->id);

                return $role;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => __('A role with this name already exists.')]);
        }
    }
}
