<?php

namespace App\Domain\Authorization\Actions;

use App\Domain\Audit\AuditLog;
use App\Domain\Authorization\Errors\AuthorizationDenied;
use App\Domain\Authorization\Models\Role;
use App\Domain\Authorization\PolicyEngine;
use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Permissions\TenancyPermission;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateRole
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
     * @throws ModelNotFoundException<Role>
     * @throws ValidationException
     */
    public function __invoke(User $actor, string $roleId, string $name, array $permissionKeys): Role
    {
        $role = $this->context->tenant()->roles()->whereNull('system_key')->findOrFail($roleId);

        $this->engine->authorize($actor, TenancyPermission::RoleManage);

        ['name' => $name, 'key' => $key, 'permissions' => $permissionKeys] = ($this->validate)($name, $permissionKeys);
        $before = ['name' => $role->name, 'permissions' => $role->permissionKeys()];

        try {
            DB::transaction(function () use ($role, $name, $key, $permissionKeys, $before, $actor): void {
                $role->update(['key' => $key, 'name' => $name]);
                $role->syncPermissions($permissionKeys);

                $this->audit->record('authorization.role.updated', $role, $before, ['name' => $name, 'permissions' => $permissionKeys], actorId: $actor->id);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => __('A role with this name already exists.')]);
        }

        return $role;
    }
}
