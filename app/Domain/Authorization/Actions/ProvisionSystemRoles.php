<?php

namespace App\Domain\Authorization\Actions;

use App\Domain\Authorization\Enums\SystemRole;
use App\Domain\Authorization\Models\Role;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\PermissionRegistry;

/**
 * Creates the system roles of a tenant and grants them their permissions.
 *
 * Idempotent: it runs when a tenant is created and again on every permission
 * synchronisation, so a new permission reaches every existing tenant.
 */
final readonly class ProvisionSystemRoles
{
    public function __construct(private PermissionRegistry $registry) {}

    /**
     * @return array<value-of<SystemRole>, Role>
     */
    public function __invoke(string $tenantId): array
    {
        $roles = [];
        $existing = Role::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('system_key')
            ->get()
            ->keyBy(fn (Role $role): string => $role->system_key->value ?? '');

        foreach (SystemRole::cases() as $systemRole) {
            $role = $existing->get($systemRole->value);

            if ($role === null) {
                $role = new Role(['key' => $systemRole->value, 'name' => ucfirst($systemRole->value)]);
                $role->tenant_id = $tenantId;
                $role->system_key = $systemRole;
                $role->save();
            }

            $role->syncPermissions(array_keys(array_filter(
                $this->registry->all(),
                fn (Permission $permission): bool => $permission->capability()->isGrantable()
                    && in_array($systemRole, $permission->systemRoles(), true),
            )));

            $roles[$systemRole->value] = $role;
        }

        return $roles;
    }
}
