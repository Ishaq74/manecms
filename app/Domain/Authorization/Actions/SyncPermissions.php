<?php

namespace App\Domain\Authorization\Actions;

use App\Domain\Authorization\Permission;
use App\Domain\Authorization\PermissionRegistry;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Copies the permissions declared in code into the database (todo/todo.md §28).
 *
 * Runs as the table owner: the application role can only read `permissions`.
 */
final readonly class SyncPermissions
{
    public function __construct(
        private PermissionRegistry $registry,
        private ProvisionSystemRoles $provisionSystemRoles,
    ) {}

    /**
     * @return array{permissions: int, removed: int, tenants: int}
     */
    public function __invoke(): array
    {
        return DB::transaction(function (): array {
            $permissions = $this->registry->all();
            $now = now();

            DB::table('permissions')->upsert(array_values(array_map(fn (Permission $permission): array => [
                'key' => $permission->key(),
                'context' => strstr($permission->key(), '.', true),
                'capability' => $permission->capability()->value,
                'requires_approval' => $permission->requiresApproval(),
                'created_at' => $now,
                'updated_at' => $now,
            ], $permissions)), ['key'], ['context', 'capability', 'requires_approval', 'updated_at']);

            // Removing a permission from code removes it from every role through the foreign key cascade.
            $removed = DB::table('permissions')->whereNotIn('key', array_keys($permissions))->delete();

            // A permission that became privileged leaves the custom roles that held it.
            DB::table('role_permissions')
                ->whereIn('permission_key', array_keys(array_filter(
                    $permissions,
                    fn (Permission $permission): bool => ! $permission->capability()->isGrantableToCustomRoles(),
                )))
                ->whereIn('role_id', fn (Builder $query): Builder => $query->select('id')->from('roles')->whereNull('system_key'))
                ->delete();

            $tenantIds = DB::table('tenants')->pluck('id')->filter(fn (mixed $id): bool => is_string($id));

            foreach ($tenantIds as $tenantId) {
                ($this->provisionSystemRoles)($tenantId);
            }

            return ['permissions' => count($permissions), 'removed' => $removed, 'tenants' => $tenantIds->count()];
        });
    }
}
