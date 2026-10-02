<?php

use App\Domain\Audit\Models\AuditEvent;
use App\Models\User;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Livewire\Livewire;

/**
 * @return list<string>
 */
function auditedActions(): array
{
    return asOwner(fn (): array => AuditEvent::query()->orderBy('occurred_at')->orderBy('id')->pluck('action')->all());
}

it('records successful and failed sign-ins and sign-outs', function (): void {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password']);
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->post(route('logout'));

    // The first sign-in also records the device it came from.
    expect(auditedActions())->toBe(['identity.login.failed', 'identity.device.recorded', 'identity.login.succeeded', 'identity.logout']);

    $failed = asOwner(fn () => AuditEvent::query()->where('action', 'identity.login.failed')->sole());

    expect($failed->tenant_id)->toBeNull()
        ->and($failed->actor_id)->toBe($user->id)
        ->and($failed->after)->toBe(['email' => strtolower($user->email)]);
});

it('records a password change without storing the password', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::settings.security')
        ->set('current_password', 'password')
        ->set('password', 'A-new-Passw0rd!')
        ->set('password_confirmation', 'A-new-Passw0rd!')
        ->call('updatePassword')
        ->assertHasNoErrors();

    $event = asOwner(fn () => AuditEvent::query()->where('action', 'identity.password.changed')->sole());

    expect($event->actor_id)->toBe($user->id)
        ->and(json_encode([$event->before, $event->after]))->not->toContain('A-new-Passw0rd!');
});

it('records the deletion of an account', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertHasNoErrors();

    $event = asOwner(fn () => AuditEvent::query()->where('action', 'identity.account.deleted')->sole());

    expect($event->actor_id)->toBe($user->id)
        ->and($event->subject_id)->toBe((string) $user->id);
});

it('records two-factor changes, including regenerated recovery codes', function (): void {
    $user = User::factory()->create();

    TwoFactorAuthenticationConfirmed::dispatch($user);
    RecoveryCodesGenerated::dispatch($user);

    expect(auditedActions())->toBe(['identity.two_factor.enabled', 'identity.two_factor.recovery_codes_regenerated']);
});
