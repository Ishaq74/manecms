<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Audit\AuditLog;
use App\Domain\Tenancy\Database\TenantDatabaseContext;
use App\Domain\Tenancy\Exceptions\InvitationRejected;
use App\Domain\Tenancy\Models\TenantInvitation;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Models\Workspace;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Finds an invitation from the link of its email and turns it into a membership.
 *
 * The invitee has no tenant context yet, so the token is resolved by the
 * `tenant_invitation_by_token` definer function; everything else runs inside
 * the tenant of the invitation, under row level security.
 */
final readonly class AcceptInvitation
{
    public function __construct(
        private TenantDatabaseContext $database,
        private AuditLog $audit,
    ) {}

    /**
     * The invitation behind a link, before the invitee confirms.
     *
     * @throws InvitationRejected
     */
    public function find(User $user, string $invitationId, #[\SensitiveParameter] string $token): TenantInvitation
    {
        $tenantId = $this->tenantOf($invitationId, $token);

        return $this->database->runAs($tenantId, $user->id, function () use ($invitationId): TenantInvitation {
            return TenantInvitation::query()->with(['tenant', 'role', 'inviter'])->findOr($invitationId, fn () => throw InvitationRejected::invalid());
        });
    }

    /**
     * @throws InvitationRejected
     */
    public function __invoke(User $user, string $invitationId, #[\SensitiveParameter] string $token): Workspace
    {
        $tenantId = $this->tenantOf($invitationId, $token);

        return $this->database->runAs($tenantId, $user->id, fn (): Workspace => DB::transaction(function () use ($user, $invitationId): Workspace {
            $invitation = TenantInvitation::query()->with('tenant')->lockForUpdate()->findOr($invitationId, fn () => throw InvitationRejected::invalid());

            $this->ensureAcceptable($invitation, $user);

            $workspaceIds = $invitation->workspace_ids ?? [];
            $workspaces = $invitation->tenant->workspaces()->active()
                ->when($workspaceIds !== [], fn (Builder $query): Builder => $query->whereIn('id', $workspaceIds))
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();
            $entry = $workspaces->first() ?? throw InvitationRejected::tenantArchived();

            $member = new TenantMember(['user_id' => $user->id, 'role_id' => $invitation->role_id, 'last_workspace_id' => $entry->id]);
            $member->tenant_id = $invitation->tenant_id;
            $member->save();

            if ($workspaceIds !== []) {
                $member->restrictedWorkspaces()->attach($workspaces->modelKeys(), ['tenant_id' => $invitation->tenant_id]);
            }

            $invitation->accepted_at = now();
            $invitation->accepted_by = $user->id;
            $invitation->save();

            $this->audit->record('tenancy.invitation.accepted', $invitation, after: ['email' => $invitation->email], actorId: $user->id);
            $this->audit->record('tenancy.member.joined', $member, after: ['user_id' => $user->id, 'role_id' => $invitation->role_id], actorId: $user->id);

            return $entry;
        }));
    }

    /**
     * @throws InvitationRejected
     */
    private function tenantOf(string $invitationId, string $token): string
    {
        if (! Str::isUlid($invitationId) || $token === '') {
            throw InvitationRejected::invalid();
        }

        $row = DB::selectOne('select id, tenant_id from tenant_invitation_by_token(?)', [TenantInvitation::hashToken($token)]);
        $row = is_object($row) ? (array) $row : [];

        if (! is_string($row['id'] ?? null) || ! is_string($row['tenant_id'] ?? null) || ! hash_equals($row['id'], $invitationId)) {
            throw InvitationRejected::invalid();
        }

        return $row['tenant_id'];
    }

    /**
     * @throws InvitationRejected
     */
    private function ensureAcceptable(TenantInvitation $invitation, User $user): void
    {
        match (true) {
            $invitation->accepted_at !== null => throw InvitationRejected::alreadyAccepted(),
            $invitation->revoked_at !== null => throw InvitationRejected::revoked(),
            $invitation->expires_at->isPast() => throw InvitationRejected::expired(),
            $invitation->tenant->isArchived() => throw InvitationRejected::tenantArchived(),
            Str::lower($user->email) !== $invitation->email => throw InvitationRejected::emailMismatch(),
            TenantMember::query()->where('tenant_id', $invitation->tenant_id)->where('user_id', $user->id)->exists() => throw InvitationRejected::alreadyMember(),
            default => null,
        };
    }
}
