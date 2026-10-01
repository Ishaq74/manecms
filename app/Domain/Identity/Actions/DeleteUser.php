<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Tenancy\Enums\TenantRole;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Deletes an account unless it still owns a tenant (todo/todo.md §20.1).
 *
 * Memberships of other roles go with the user through the foreign key cascade;
 * tenant data is never deleted with an account.
 */
final class DeleteUser
{
    /**
     * @param  callable(): mixed  $signOut  Runs once deletion is allowed, while the row still exists.
     *
     * @throws ValidationException
     */
    public function __invoke(User $user, callable $signOut): void
    {
        if ($user->tenantMemberships()->where('role', TenantRole::Owner)->exists()) {
            throw ValidationException::withMessages([
                'password' => __('You own a space. Transfer its ownership before deleting your account.'),
            ]);
        }

        $signOut();

        $user->delete();
    }
}
