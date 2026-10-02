<?php

namespace App\Domain\Authorization\Actions;

use App\Domain\Authorization\Permission;
use App\Domain\Authorization\PermissionRegistry;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Validation shared by the creation and the update of a custom role.
 */
final readonly class ValidateCustomRole
{
    public function __construct(private PermissionRegistry $registry) {}

    /**
     * @param  list<string>  $permissionKeys
     * @return array{name: string, key: string, permissions: list<string>}
     *
     * @throws ValidationException
     */
    public function __invoke(string $name, array $permissionKeys): array
    {
        $name = Str::squish($name);

        Validator::make(['name' => $name], ['name' => ['required', 'string', 'min:2', 'max:80']])->validate();

        $key = Str::slug($name);

        if ($key === '' || in_array($key, ['owner', 'admin', 'member'], true)) {
            throw ValidationException::withMessages(['name' => __('Choose another name for this role.')]);
        }

        $grantable = array_keys(array_filter(
            $this->registry->all(),
            fn (Permission $permission): bool => $permission->capability()->isGrantableToCustomRoles(),
        ));
        $permissionKeys = array_values(array_unique($permissionKeys));

        if (array_diff($permissionKeys, $grantable) !== []) {
            throw ValidationException::withMessages(['permissions' => __('Some of these permissions cannot be given to a custom role.')]);
        }

        sort($permissionKeys);

        return ['name' => $name, 'key' => $key, 'permissions' => $permissionKeys];
    }
}
