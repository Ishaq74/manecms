<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Audit\AuditLog;
use App\Domain\Authorization\Errors\AuthorizationDenied;
use App\Domain\Authorization\PolicyEngine;
use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Models\TenantInvitation;
use App\Domain\Tenancy\Permissions\TenancyPermission;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final readonly class RevokeInvitation
{
    public function __construct(
        private TenantContext $context,
        private PolicyEngine $engine,
        private AuditLog $audit,
    ) {}

    /**
     * @throws AuthorizationDenied
     * @throws ModelNotFoundException<TenantInvitation>
     */
    public function __invoke(User $actor, string $invitationId): TenantInvitation
    {
        $invitation = $this->context->tenant()->invitations()->open()->with('role')->findOrFail($invitationId);

        $this->engine->authorize($actor, TenancyPermission::MemberInvite, assignedRole: $invitation->role);

        DB::transaction(function () use ($invitation, $actor): void {
            $invitation->revoked_at = now();
            $invitation->save();

            $this->audit->record('tenancy.invitation.revoked', $invitation, after: ['email' => $invitation->email], actorId: $actor->id);
        });

        return $invitation;
    }
}
