<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Audit\AuditLog;
use App\Domain\Authorization\Errors\AuthorizationDenied;
use App\Domain\Authorization\PolicyEngine;
use App\Domain\Identity\Actions\ConfirmIdentity;
use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Permissions\TenancyPermission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Archives the current tenant: it leaves every switcher and answers 404.
 * Its data is kept; the owner may then delete their account (todo/todo.md §20.1).
 */
final readonly class ArchiveTenant
{
    public function __construct(
        private TenantContext $context,
        private PolicyEngine $engine,
        private ConfirmIdentity $confirmIdentity,
        private AuditLog $audit,
    ) {}

    /**
     * @throws AuthorizationDenied
     * @throws ValidationException
     */
    public function __invoke(User $actor, string $confirmation, #[\SensitiveParameter] string $password, #[\SensitiveParameter] ?string $code = null): Tenant
    {
        $tenant = $this->context->tenant();

        $this->engine->authorize($actor, TenancyPermission::TenantArchive);

        if (trim($confirmation) !== $tenant->name) {
            throw ValidationException::withMessages(['confirmation' => __('Type the exact name of the space to confirm.')]);
        }

        ($this->confirmIdentity)($actor, $password, $code);

        DB::transaction(function () use ($tenant, $actor): void {
            $locked = Tenant::query()->whereKey($tenant->id)->lockForUpdate()->sole();

            if ($locked->isArchived()) {
                return;
            }

            $locked->archived_at = now();
            $locked->save();

            $this->audit->record('tenancy.tenant.archived', $locked, ['archived' => false], ['archived' => true], actorId: $actor->id);
        });

        return $tenant->refresh();
    }
}
