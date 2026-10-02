<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Audit\AuditLog;
use App\Domain\Authorization\Errors\AuthorizationDenied;
use App\Domain\Authorization\Models\Role;
use App\Domain\Authorization\PolicyEngine;
use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Models\TenantInvitation;
use App\Domain\Tenancy\Notifications\TenantInvitationNotification;
use App\Domain\Tenancy\Permissions\TenancyPermission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Invites a person by email to join the current tenant (todo/todo.md §21).
 */
final readonly class InviteMember
{
    private const int MAX_PER_HOUR = 30;

    private const int TOKEN_LENGTH = 48;

    public function __construct(
        private TenantContext $context,
        private PolicyEngine $engine,
        private AuditLog $audit,
    ) {}

    /**
     * @param  list<string>  $workspaceIds  Empty for every workspace.
     *
     * @throws AuthorizationDenied
     * @throws ModelNotFoundException<Role>
     * @throws ValidationException
     */
    public function __invoke(User $actor, string $email, string $roleId, array $workspaceIds = []): TenantInvitation
    {
        $tenant = $this->context->tenant();
        $role = $tenant->roles()->findOrFail($roleId);

        $this->engine->authorize($actor, TenancyPermission::MemberInvite, assignedRole: $role);

        $email = $this->validatedEmail($email);
        $workspaceIds = $this->validatedWorkspaces($workspaceIds);
        $this->throttle($actor);

        if ($tenant->members()->whereHas('user', fn (Builder $user): Builder => $user->whereRaw('lower(email) = ?', [$email]))->exists()) {
            throw ValidationException::withMessages(['email' => __('This person is already a member of the space.')]);
        }

        $token = Str::random(self::TOKEN_LENGTH);

        try {
            $invitation = DB::transaction(function () use ($tenant, $actor, $email, $role, $workspaceIds, $token): TenantInvitation {
                $this->closeExpiredInvitation($email, $actor);

                $invitation = $tenant->invitations()->create([
                    'email' => $email,
                    'role_id' => $role->id,
                    'workspace_ids' => $workspaceIds === [] ? null : $workspaceIds,
                    'token_hash' => TenantInvitation::hashToken($token),
                    'invited_by' => $actor->id,
                    'expires_at' => now()->addDays(TenantInvitation::VALID_DAYS),
                ]);

                $this->audit->record('tenancy.invitation.created', $invitation, after: [
                    'email' => $email,
                    'role' => $role->key,
                    'workspaces' => $workspaceIds,
                ], actorId: $actor->id);

                return $invitation;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => __('An invitation is already pending for this email.')]);
        }

        Notification::route('mail', $email)->notify(new TenantInvitationNotification($invitation, $tenant->name, $actor->name, $token));

        return $invitation;
    }

    /**
     * @throws ValidationException
     */
    private function validatedEmail(string $email): string
    {
        $email = Str::lower(trim($email));

        Validator::make(['email' => $email], ['email' => ['required', 'string', 'email', 'max:254']])->validate();

        return $email;
    }

    /**
     * @param  list<string>  $workspaceIds
     * @return list<string>
     *
     * @throws ValidationException
     */
    private function validatedWorkspaces(array $workspaceIds): array
    {
        $workspaceIds = array_values(array_unique($workspaceIds));
        $known = $this->context->tenant()->workspaces()->active()->whereIn('id', $workspaceIds)->count();

        if ($known !== count($workspaceIds)) {
            throw ValidationException::withMessages(['workspaces' => __('Choose workspaces of this space.')]);
        }

        return $workspaceIds;
    }

    /**
     * @throws ValidationException
     */
    private function throttle(User $actor): void
    {
        $key = 'invite-member:'.$actor->id;

        if (RateLimiter::tooManyAttempts($key, self::MAX_PER_HOUR)) {
            throw ValidationException::withMessages(['email' => __('You have sent too many invitations. Try again in :minutes minutes.', [
                'minutes' => (int) ceil(RateLimiter::availableIn($key) / 60),
            ])]);
        }

        RateLimiter::hit($key, 3600);
    }

    /**
     * An expired invitation still holds the "one pending invitation" slot until it is closed.
     */
    private function closeExpiredInvitation(string $email, User $actor): void
    {
        $previous = $this->context->tenant()->invitations()->open()->where('email', $email)->lockForUpdate()->first();

        if ($previous === null) {
            return;
        }

        if (! $previous->expires_at->isPast()) {
            throw ValidationException::withMessages(['email' => __('An invitation is already pending for this email.')]);
        }

        $previous->revoked_at = now();
        $previous->save();

        $this->audit->record('tenancy.invitation.expired', $previous, after: ['email' => $email], actorId: $actor->id);
    }
}
