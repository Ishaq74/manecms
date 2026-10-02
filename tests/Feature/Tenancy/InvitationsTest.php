<?php

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Authorization\Enums\SystemRole;
use App\Domain\Authorization\Errors\AuthorizationDenied;
use App\Domain\Authorization\Models\Role;
use App\Domain\Tenancy\Actions\AcceptInvitation;
use App\Domain\Tenancy\Actions\InviteMember;
use App\Domain\Tenancy\Actions\RevokeInvitation;
use App\Domain\Tenancy\Database\TenantDatabaseContext;
use App\Domain\Tenancy\Exceptions\InvitationRejected;
use App\Domain\Tenancy\Models\TenantInvitation;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Models\Workspace;
use App\Domain\Tenancy\Notifications\TenantInvitationNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/**
 * Invite through the action and return the token the email carries.
 *
 * @param  list<string>  $workspaceIds
 * @return array{0: TenantInvitation, 1: string}
 */
function inviteByEmail(User $actor, string $email, Role $role, array $workspaceIds = []): array
{
    Notification::fake();

    $invitation = app(InviteMember::class)($actor, $email, $role->id, $workspaceIds);
    $token = null;

    Notification::assertSentOnDemand(TenantInvitationNotification::class, function (TenantInvitationNotification $notification, array $channels, AnonymousNotifiable $notifiable) use (&$token, $email): bool {
        $token = $notification->token;

        return $notifiable->routes['mail'] === mb_strtolower(trim($email));
    });

    return [$invitation, (string) $token];
}

function systemRoleOf(TenantMember $member, SystemRole $role): Role
{
    return asOwner(fn () => $member->tenant->roles()->where('system_key', $role)->sole());
}

it('emails an invitation and stores only the hash of its token', function (): void {
    [$owner, $workspace, $member] = joinWorkspace();
    enterWorkspace($member, $workspace);

    [$invitation, $token] = inviteByEmail($owner, '  Lea@Example.TEST ', systemRoleOf($member, SystemRole::Member));

    expect($invitation->email)->toBe('lea@example.test')
        ->and(strlen($token))->toBe(48)
        ->and($invitation->token_hash)->toBe(hash('sha256', $token))
        ->and(TenantInvitation::query()->where('token_hash', $token)->exists())->toBeFalse()
        ->and($invitation->expires_at->isSameDay(now()->addDays(7)))->toBeTrue()
        ->and(AuditEvent::query()->where('action', 'tenancy.invitation.created')->sole()->after)->not->toHaveKey('token');
});

it('keeps a single pending invitation per email, and replaces an expired one', function (): void {
    [$owner, $workspace, $member] = joinWorkspace();
    enterWorkspace($member, $workspace);
    $role = systemRoleOf($member, SystemRole::Member);

    inviteByEmail($owner, 'lea@example.test', $role);

    expect(fn () => inviteByEmail($owner, 'lea@example.test', $role))->toThrow(ValidationException::class);

    $this->travel(8)->days();
    [$second] = inviteByEmail($owner, 'lea@example.test', $role);

    expect(TenantInvitation::query()->where('email', 'lea@example.test')->count())->toBe(2)
        ->and(TenantInvitation::query()->open()->sole()->id)->toBe($second->id)
        ->and(AuditEvent::query()->where('action', 'tenancy.invitation.expired')->count())->toBe(1);
});

it('refuses to invite an existing member', function (): void {
    [$owner, $workspace, $member] = joinWorkspace();
    $existing = TenantMember::factory()->member()->for($member->tenant)->create();
    enterWorkspace($member, $workspace);

    expect(fn () => inviteByEmail($owner, strtoupper($existing->user->email), systemRoleOf($member, SystemRole::Member)))
        ->toThrow(ValidationException::class);
});

it('refuses the owner role and roles from another tenant', function (): void {
    [$owner, $workspace, $member] = joinWorkspace();
    $foreign = Role::factory()->create();
    enterWorkspace($member, $workspace);

    expect(fn () => inviteByEmail($owner, 'x@example.test', systemRoleOf($member, SystemRole::Owner)))->toThrow(AuthorizationDenied::class)
        ->and(fn () => app(InviteMember::class)($owner, 'x@example.test', $foreign->id))->toThrow(ModelNotFoundException::class);
});

it('does not let a member invite', function (): void {
    [$user, $workspace, $member] = joinWorkspace(SystemRole::Member);
    enterWorkspace($member, $workspace);

    expect(fn () => inviteByEmail($user, 'x@example.test', systemRoleOf($member, SystemRole::Member)))->toThrow(AuthorizationDenied::class);
});

it('turns an accepted invitation into a membership with its role and workspaces', function (): void {
    [$owner, $workspace, $member] = joinWorkspace();
    $marketing = Workspace::factory()->for($member->tenant)->create(['name' => 'Marketing']);
    enterWorkspace($member, $workspace);
    [$invitation, $token] = inviteByEmail($owner, 'lea@example.test', systemRoleOf($member, SystemRole::Admin), [$marketing->id]);

    $lea = User::factory()->create(['email' => 'Lea@example.test']);
    $entry = app(AcceptInvitation::class)($lea, $invitation->id, $token);

    $joined = asOwner(fn () => TenantMember::query()->with(['role', 'restrictedWorkspaces'])->where('user_id', $lea->id)->sole());

    expect($entry->id)->toBe($marketing->id)
        ->and($joined->role->system_key)->toBe(SystemRole::Admin)
        ->and($joined->restrictedWorkspaces->modelKeys())->toBe([$marketing->id])
        ->and(asOwner(fn () => $invitation->fresh()?->accepted_by))->toBe($lea->id)
        ->and(asOwner(fn () => AuditEvent::query()->where('action', 'tenancy.member.joined')->exists()))->toBeTrue();
});

it('rejects an invitation that cannot be accepted', function (Closure $prepare, string $code): void {
    [$owner, $workspace, $member] = joinWorkspace();
    enterWorkspace($member, $workspace);
    [$invitation, $token] = inviteByEmail($owner, 'lea@example.test', systemRoleOf($member, SystemRole::Member));
    $lea = User::factory()->create(['email' => 'lea@example.test']);

    [$user, $token] = $prepare($invitation, $token, $lea, $member);

    expect(fn () => app(AcceptInvitation::class)($user, $invitation->id, $token))
        ->toThrow(fn (InvitationRejected $rejected) => expect($rejected->errorCode())->toBe($code));
})->with([
    'wrong token' => [fn ($invitation, $token, $lea) => [$lea, str_repeat('x', 48)], 'INVITATION_INVALID'],
    'expired' => [function ($invitation, $token, $lea) {
        test()->travel(8)->days();

        return [$lea, $token];
    }, 'INVITATION_EXPIRED'],
    'revoked' => [function ($invitation, $token, $lea) {
        asOwner(fn () => $invitation->forceFill(['revoked_at' => now()])->save());

        return [$lea, $token];
    }, 'INVITATION_REVOKED'],
    'already used' => [function ($invitation, $token, $lea) {
        app(AcceptInvitation::class)($lea, $invitation->id, $token);

        return [User::factory()->create(['email' => 'lea2@example.test']), $token];
    }, 'INVITATION_ALREADY_ACCEPTED'],
    'other email' => [fn ($invitation, $token) => [User::factory()->create(['email' => 'mallory@example.test']), $token], 'INVITATION_EMAIL_MISMATCH'],
    'already member' => [function ($invitation, $token, $lea, $member) {
        TenantMember::factory()->member()->for($member->tenant)->create(['user_id' => $lea->id]);

        return [$lea, $token];
    }, 'INVITATION_ALREADY_MEMBER'],
    'archived space' => [function ($invitation, $token, $lea, $member) {
        asOwner(fn () => $member->tenant->forceFill(['archived_at' => now()])->save());

        return [$lea, $token];
    }, 'INVITATION_TENANT_ARCHIVED'],
]);

it('revokes a pending invitation', function (): void {
    [$owner, $workspace, $member] = joinWorkspace();
    enterWorkspace($member, $workspace);
    [$invitation] = inviteByEmail($owner, 'lea@example.test', systemRoleOf($member, SystemRole::Member));

    app(RevokeInvitation::class)($owner, $invitation->id);

    expect($invitation->fresh()?->revoked_at)->not->toBeNull()
        ->and(AuditEvent::query()->where('action', 'tenancy.invitation.revoked')->exists())->toBeTrue();
});

it('shows the invitation page to its recipient and joins the space from it', function (): void {
    [$owner, $workspace, $member] = joinWorkspace();
    enterWorkspace($member, $workspace);
    [$invitation, $token] = inviteByEmail($owner, 'lea@example.test', systemRoleOf($member, SystemRole::Member));
    app(TenantDatabaseContext::class)->clear();
    $lea = User::factory()->create(['email' => 'lea@example.test']);

    $this->actingAs($lea)
        ->get(route('invitations.show', ['invitation' => $invitation->id, 'token' => $token]))
        ->assertOk()
        ->assertSee($member->tenant->name);

    Livewire::actingAs($lea)
        ->test('pages::invitations.show', ['invitation' => $invitation->id, 'token' => $token])
        ->call('accept')
        ->assertRedirect(route('workspace.home', $workspace));
});

it('answers 404 to a forged invitation link and sends guests to the login page', function (): void {
    $invitation = TenantInvitation::factory()->create();
    $user = User::factory()->create();

    $this->get(route('invitations.show', ['invitation' => $invitation->id, 'token' => 'nope']))->assertRedirect(route('login'));

    $this->actingAs($user)
        ->get(route('invitations.show', ['invitation' => $invitation->id, 'token' => str_repeat('a', 48)]))
        ->assertNotFound();
});

it('lists members and invitations on the members page', function (): void {
    [$owner, $workspace, $member] = joinWorkspace();
    $colleague = TenantMember::factory()->member()->for($member->tenant)->create();
    TenantInvitation::factory()->for($member->tenant)->create(['email' => 'pending@example.test']);

    $this->actingAs($owner)
        ->get(route('members.index', $workspace))
        ->assertOk()
        ->assertSee($colleague->user->name)
        ->assertSee('pending@example.test');
});

it('invites from the members page', function (): void {
    [$owner, $workspace, $member] = joinWorkspace();
    enterWorkspace($member, $workspace);
    Notification::fake();

    Livewire::actingAs($owner)
        ->test('pages::members.index')
        ->call('openInvite')
        ->set('inviteEmail', 'nouveau@example.test')
        ->call('invite')
        ->assertHasNoErrors()
        ->assertSet('showInviteModal', false);

    Notification::assertSentOnDemandTimes(TenantInvitationNotification::class, 1);
});
