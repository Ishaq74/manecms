<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Audit\AuditLog;
use App\Domain\Tenancy\Database\TenantDatabaseContext;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Deletes an account unless it still owns an active tenant (todo/todo.md §20.1).
 *
 * Memberships of other roles go with the user through the foreign key cascade;
 * tenant data is never deleted with an account.
 */
final readonly class DeleteUser
{
    public function __construct(
        private TenantDatabaseContext $database,
        private AuditLog $audit,
    ) {}

    /**
     * @param  callable(): mixed  $signOut  Runs once deletion is allowed, while the row still exists.
     *
     * @throws ValidationException
     */
    public function __invoke(User $user, callable $signOut): void
    {
        // Without an explicit user scope, row level security would hide the memberships and let the deletion through.
        $ownsTenant = $this->database->runAs(null, $user->id, fn (): bool => $user->tenantMemberships()
            ->where('is_owner', true)
            ->whereHas('tenant', fn (Builder $tenant): Builder => $tenant->whereNull('archived_at'))
            ->exists());

        if ($ownsTenant) {
            throw ValidationException::withMessages([
                'password' => __('You own a space. Transfer its ownership before deleting your account.'),
            ]);
        }

        $signOut();

        DB::transaction(function () use ($user): void {
            $this->audit->record('identity.account.deleted', $user, actorId: $user->id, platform: true);
            $user->delete();
        });
    }
}
