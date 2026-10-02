<?php

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Authorization\Enums\SystemRole;
use App\Domain\Authorization\Errors\AuthorizationDenied;
use App\Domain\Tenancy\Actions\ArchiveTenant;
use App\Domain\Tenancy\Actions\ResolveEntryWorkspace;
use App\Domain\Tenancy\Actions\TransferOwnership;
use App\Domain\Tenancy\Actions\UpdateTenantSecurity;
use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Database\TenantDatabaseContext;
use App\Domain\Tenancy\Models\TenantMember;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

function enableTwoFactor(User $user): string
{
    $provider = app(TwoFactorAuthenticationProvider::class);
    $secret = $provider->generateSecretKey();

    $user->forceFill([
        'two_factor_secret' => encrypt($secret),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    return $secret;
}

function currentCode(string $secret): string
{
    return app(Google2FA::class)->getCurrentOtp($secret);
}

it('transfers ownership: the new owner gets every right and the old one becomes admin', function (): void {
    [$owner, $workspace, $ownerMember] = joinWorkspace();
    $heir = TenantMember::factory()->member()->for($ownerMember->tenant)->create();
    asOwner(fn () => $heir->restrictedWorkspaces()->attach($workspace->id, ['tenant_id' => $heir->tenant_id]));
    enterWorkspace($ownerMember, $workspace);

    app(TransferOwnership::class)($owner, $heir->id, 'password');

    [$previous, $next] = asOwner(fn (): array => [
        TenantMember::query()->with('role')->find($ownerMember->id),
        TenantMember::query()->with(['role', 'restrictedWorkspaces'])->find($heir->id),
    ]);

    expect($previous?->is_owner)->toBeFalse()
        ->and($previous?->role->system_key)->toBe(SystemRole::Admin)
        ->and($next?->is_owner)->toBeTrue()
        ->and($next?->restrictedWorkspaces)->toBeEmpty()
        ->and(asOwner(fn () => AuditEvent::query()->where('action', 'tenancy.tenant.ownership_transferred')->sole()->after))->toBe(['owner_id' => $heir->user_id]);
});

it('asks for the password, and for a two-factor code when it is enabled', function (): void {
    [$owner, $workspace, $ownerMember] = joinWorkspace();
    $heir = TenantMember::factory()->member()->for($ownerMember->tenant)->create();
    $secret = enableTwoFactor($owner);
    enterWorkspace($ownerMember, $workspace);

    expect(fn () => app(TransferOwnership::class)($owner, $heir->id, 'wrong'))
        ->toThrow(fn (ValidationException $exception) => expect($exception->errors())->toHaveKey('password'))
        ->and(fn () => app(TransferOwnership::class)($owner, $heir->id, 'password', '000000'))
        ->toThrow(fn (ValidationException $exception) => expect($exception->errors())->toHaveKey('code'));

    app(TransferOwnership::class)($owner, $heir->id, 'password', currentCode($secret));

    expect(asOwner(fn () => TenantMember::query()->find($heir->id)?->is_owner))->toBeTrue();
});

it('lets only one of two concurrent transfers succeed', function (): void {
    [$owner, $workspace, $ownerMember] = joinWorkspace();
    $first = TenantMember::factory()->member()->for($ownerMember->tenant)->create();
    $second = TenantMember::factory()->member()->for($ownerMember->tenant)->create();
    enterWorkspace($ownerMember, $workspace);

    app(TransferOwnership::class)($owner, $first->id, 'password');

    // The second request was authorised with the stale context of the former owner; the lock re-reads it.
    expect(fn () => app(TransferOwnership::class)($owner, $second->id, 'password'))->toThrow(AuthorizationDenied::class)
        ->and(asOwner(fn () => TenantMember::query()->where('tenant_id', $ownerMember->tenant_id)->where('is_owner', true)->pluck('id')->all()))->toBe([$first->id]);
});

it('keeps a single owner per tenant in the database, whatever the code does', function (): void {
    $owner = TenantMember::factory()->owner()->create();
    $member = TenantMember::factory()->member()->for(asOwner(fn () => $owner->tenant))->create();
    $ownerRoleId = $owner->role_id;

    expect(fn () => asOwner(fn () => $member->update(['role_id' => $ownerRoleId])))->toThrow(UniqueConstraintViolationException::class);
});

it('refuses a transfer to a member of another tenant or to oneself', function (): void {
    [$owner, $workspace, $ownerMember] = joinWorkspace();
    $foreign = TenantMember::factory()->member()->create();
    enterWorkspace($ownerMember, $workspace);

    expect(fn () => app(TransferOwnership::class)($owner, $foreign->id, 'password'))->toThrow(ModelNotFoundException::class)
        ->and(fn () => app(TransferOwnership::class)($owner, $ownerMember->id, 'password'))->toThrow(ValidationException::class);
});

it('does not let an admin transfer the space', function (): void {
    [$admin, $workspace, $adminMember] = joinWorkspace(SystemRole::Admin);
    $member = TenantMember::factory()->member()->for($adminMember->tenant)->create();
    enterWorkspace($adminMember, $workspace);

    expect(fn () => app(TransferOwnership::class)($admin, $member->id, 'password'))->toThrow(AuthorizationDenied::class);
});

it('archives the space: it answers 404, leaves the switcher and frees the owner to delete their account', function (): void {
    [$owner, $workspace, $ownerMember] = joinWorkspace();
    enterWorkspace($ownerMember, $workspace);

    expect(fn () => app(ArchiveTenant::class)($owner, 'Wrong name', 'password'))->toThrow(ValidationException::class);

    app(ArchiveTenant::class)($owner, $ownerMember->tenant->name, 'password');
    app(TenantDatabaseContext::class)->clear();

    $this->actingAs($owner)->get(route('workspace.home', $workspace))->assertNotFound();

    expect(app(ResolveEntryWorkspace::class)($owner))->toBeNull()
        ->and(asOwner(fn () => AuditEvent::query()->where('action', 'tenancy.tenant.archived')->exists()))->toBeTrue();

    Livewire::actingAs($owner)
        ->test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertHasNoErrors();

    expect(User::query()->whereKey($owner->id)->exists())->toBeFalse();
});

it('requires the owner to have two-factor authentication before requiring it from others', function (): void {
    [$owner, $workspace, $ownerMember] = joinWorkspace();
    enterWorkspace($ownerMember, $workspace);

    expect(fn () => app(UpdateTenantSecurity::class)($owner, true))->toThrow(ValidationException::class);

    enableTwoFactor($owner);
    app(UpdateTenantSecurity::class)($owner, true);

    expect(app(TenantContext::class)->tenant()->fresh()?->require_mfa)->toBeTrue()
        ->and(AuditEvent::query()->where('action', 'tenancy.tenant.security_updated')->exists())->toBeTrue();
});

it('sends privileged members without 2FA to their security settings when the space requires it', function (): void {
    [$admin, $workspace, $adminMember] = joinWorkspace(SystemRole::Admin);
    $member = TenantMember::factory()->member()->for($adminMember->tenant)->create();
    asOwner(fn () => $adminMember->tenant->forceFill(['require_mfa' => true])->save());

    $this->actingAs($admin)->get(route('workspace.home', $workspace))->assertRedirect(route('security.edit', ['required' => 'mfa']));
    $this->actingAs($member->user)->get(route('workspace.home', $workspace))->assertOk();

    enableTwoFactor($admin);

    $this->actingAs($admin)->get(route('workspace.home', $workspace))->assertOk();
});

it('answers MFA_REQUIRED to Livewire requests of a privileged member without 2FA', function (): void {
    [$admin, $workspace, $adminMember] = joinWorkspace(SystemRole::Admin);
    asOwner(fn () => $adminMember->tenant->forceFill(['require_mfa' => true])->save());

    $this->actingAs($admin)
        ->withHeader('X-Livewire', 'true')
        ->getJson(route('workspace.home', $workspace))
        ->assertForbidden()
        ->assertJsonPath('code', 'MFA_REQUIRED');
});

it('shows the security, transfer and archive sections to the owner only', function (): void {
    [$owner, $workspace, $ownerMember] = joinWorkspace();
    TenantMember::factory()->member()->for($ownerMember->tenant)->create();
    [$admin, $adminWorkspace] = joinWorkspace(SystemRole::Admin);

    $this->actingAs($owner)->get(route('tenant.settings', $workspace))
        ->assertSee('data-test="require-mfa"', false)
        ->assertSee('data-test="transfer-ownership-button"', false)
        ->assertSee('data-test="archive-tenant-button"', false);

    $this->actingAs($admin)->get(route('tenant.settings', $adminWorkspace))
        ->assertOk()
        ->assertDontSee('data-test="require-mfa"', false)
        ->assertDontSee('data-test="archive-tenant-button"', false);
});
