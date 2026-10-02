<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Audit\AuditLog;
use App\Domain\Authorization\Enums\DenialReason;
use App\Domain\Authorization\Enums\SystemRole;
use App\Domain\Authorization\Errors\AuthorizationDenied;
use App\Domain\Authorization\PolicyEngine;
use App\Domain\Identity\Actions\ConfirmIdentity;
use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Permissions\TenancyPermission;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Hands the tenant over to another member; the previous owner becomes admin.
 *
 * The tenant row is locked and ownership re-read under the lock, so two
 * concurrent transfers cannot both succeed; the single-owner index is the last
 * line of defence.
 */
final readonly class TransferOwnership
{
    public function __construct(
        private TenantContext $context,
        private PolicyEngine $engine,
        private ConfirmIdentity $confirmIdentity,
        private AuditLog $audit,
    ) {}

    /**
     * @throws AuthorizationDenied
     * @throws ModelNotFoundException<TenantMember>
     * @throws ValidationException
     */
    public function __invoke(User $actor, string $memberId, #[\SensitiveParameter] string $password, #[\SensitiveParameter] ?string $code = null): TenantMember
    {
        $tenant = $this->context->tenant();
        $target = $tenant->members()->findOrFail($memberId);

        $this->engine->authorize($actor, TenancyPermission::TenantTransfer);

        if ($target->user_id === $actor->id) {
            throw ValidationException::withMessages(['member' => __('Choose another member of the space.')]);
        }

        ($this->confirmIdentity)($actor, $password, $code);

        return DB::transaction(function () use ($tenant, $target, $actor): TenantMember {
            Tenant::query()->whereKey($tenant->id)->lockForUpdate()->sole();

            $current = $tenant->members()->where('user_id', $actor->id)->lockForUpdate()->first();
            $target = $tenant->members()->whereKey($target->id)->lockForUpdate()->first();

            if ($current === null || ! $current->is_owner) {
                throw new AuthorizationDenied(DenialReason::MissingPermission);
            }

            if ($target === null) {
                throw ValidationException::withMessages(['member' => __('This member has left the space.')]);
            }

            $current->role_id = $tenant->roles()->where('system_key', SystemRole::Admin)->sole()->id;
            $current->save();

            $target->role_id = $tenant->roles()->where('system_key', SystemRole::Owner)->sole()->id;
            $target->save();

            // The owner is never restricted to some workspaces.
            $target->restrictedWorkspaces()->detach();

            $this->audit->record('tenancy.tenant.ownership_transferred', $tenant, ['owner_id' => $actor->id], ['owner_id' => $target->user_id], actorId: $actor->id);

            return $target;
        });
    }
}
