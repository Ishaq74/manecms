<?php

namespace App\Domain\Authorization;

use BackedEnum;
use LogicException;

/**
 * The permissions declared by every context, read from config/authorization.php.
 */
final class PermissionRegistry
{
    /**
     * @return array<string, Permission>
     */
    public function all(): array
    {
        $permissions = [];

        foreach ((array) config('authorization.permissions', []) as $enum) {
            if (! is_string($enum) || ! is_subclass_of($enum, BackedEnum::class) || ! is_subclass_of($enum, Permission::class)) {
                throw new LogicException('Every entry of authorization.permissions must be a backed enum implementing '.Permission::class.'.');
            }

            foreach ($enum::cases() as $permission) {
                $permissions[$permission->key()] = $permission;
            }
        }

        ksort($permissions);

        return $permissions;
    }

    public function find(string $key): ?Permission
    {
        return $this->all()[$key] ?? null;
    }
}
