<?php

namespace App\Domain\Authorization\Actions;

use App\Domain\Audit\AuditLog;
use App\Domain\Authorization\Errors\AuthorizationDenied;
use App\Domain\Authorization\Models\Role;
use App\Domain\Authorization\PolicyEngine;
use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Models\TenantInvitation;
use App\Domain\Tenancy\Permissions\TenancyPermission;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class DeleteRole
{
    public function __construct(
        private TenantContext $context,
        private PolicyEngine $engine,
        private AuditLog $audit,
    ) {}

    /**
     * @throws AuthorizationDenied
     * @throws ModelNotFoundException<Role>
     * @throws ValidationException
     */
    public function __invoke(User $actor, string $roleId): void
    {
        $role = $this->context->tenant()->roles()->whereNull('system_key')->findOrFail($roleId);

        $this->engine->authorize($actor, TenancyPermission::RoleManage);

        DB::transaction(function () use ($role, $actor): void {
            $locked = Role::query()->whereKey($role->id)->lockForUpdate()->sole();

            if ($locked->members()->exists()) {
                throw ValidationException::withMessages(['role' => __('Give its members another role before deleting it.')]);
            }

            if (TenantInvitation::query()->where('role_id', $locked->id)->open()->exists()) {
                throw ValidationException::withMessages(['role' => __('Invitations use this role: revoke them before deleting it.')]);
            }

            // Accepted and revoked invitations go with the role; the audit trail keeps their history.
            TenantInvitation::query()->where('role_id', $locked->id)->delete();

            $this->audit->record('authorization.role.deleted', $locked, ['name' => $locked->name, 'permissions' => $locked->permissionKeys()], actorId: $actor->id);
            $locked->delete();
        });
    }
}
