<?php

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Identity\Actions\RevokeSessions;
use App\Domain\Identity\Models\UserDevice;
use App\Domain\Identity\Notifications\NewSignInNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function openSession(User $user, string $id, string $userAgent = 'Mozilla/5.0 (Windows NT 10.0) Firefox/130.0'): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $user->id,
        'ip_address' => '192.0.2.10',
        'user_agent' => $userAgent,
        'payload' => base64_encode('a:0:{}'),
        'last_activity' => now()->getTimestamp(),
    ]);
}

it('lists the sessions of the account without exposing their ids', function (): void {
    $user = User::factory()->create();
    openSession($user, str_repeat('a', 40));
    openSession(User::factory()->create(), str_repeat('b', 40));

    Livewire::actingAs($user)
        ->test('pages::settings.sessions')
        ->assertSee('Firefox on Windows')
        ->assertDontSee(str_repeat('a', 40))
        ->assertSee(RevokeSessions::keyOf(str_repeat('a', 40)));
});

it('signs out one other session and never the current one', function (): void {
    $user = User::factory()->create();
    openSession($user, str_repeat('a', 40));
    openSession($user, str_repeat('c', 40));

    app(RevokeSessions::class)->one($user, RevokeSessions::keyOf(str_repeat('a', 40)), str_repeat('c', 40));

    expect(DB::table('sessions')->pluck('id')->all())->toBe([str_repeat('c', 40)])
        ->and(fn () => app(RevokeSessions::class)->one($user, RevokeSessions::keyOf(str_repeat('c', 40)), str_repeat('c', 40)))
        ->toThrow(ValidationException::class)
        ->and(asOwner(fn () => AuditEvent::query()->where('action', 'identity.session.revoked')->count()))->toBe(1);
});

it('cannot reach the sessions of another account', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    openSession($other, str_repeat('d', 40));

    expect(fn () => app(RevokeSessions::class)->one($user, RevokeSessions::keyOf(str_repeat('d', 40)), str_repeat('e', 40)))
        ->toThrow(ValidationException::class)
        ->and(DB::table('sessions')->where('id', str_repeat('d', 40))->exists())->toBeTrue();
});

it('signs out every other session with the password and rotates the remember token', function (): void {
    $user = User::factory()->create(['remember_token' => 'old-token']);
    openSession($user, str_repeat('a', 40));
    openSession($user, str_repeat('b', 40));
    openSession($user, str_repeat('c', 40));

    expect(fn () => app(RevokeSessions::class)->others($user, 'wrong', str_repeat('c', 40)))->toThrow(ValidationException::class);

    $count = app(RevokeSessions::class)->others($user, 'password', str_repeat('c', 40));

    expect($count)->toBe(2)
        ->and(DB::table('sessions')->pluck('id')->all())->toBe([str_repeat('c', 40)])
        ->and($user->fresh()?->remember_token)->not->toBe('old-token');
});

it('records the device of a sign-in and warns about a new device, but not about the first one', function (): void {
    Notification::fake();
    $user = User::factory()->create();

    $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0) Firefox/130.0')
        ->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    auth()->logout();

    Notification::assertNothingSent();

    $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0) Firefox/130.0')
        ->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    auth()->logout();

    Notification::assertNothingSent();

    $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0) Safari/604.1')
        ->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    Notification::assertSentTo($user, NewSignInNotification::class, fn (NewSignInNotification $notification): bool => $notification->device === 'Safari on iOS');

    expect(UserDevice::query()->where('user_id', $user->id)->count())->toBe(2);
});

it('treats another address on the same network as the same device', function (): void {
    expect(UserDevice::fingerprint('Agent', '203.0.113.10'))->toBe(UserDevice::fingerprint('Agent', '203.0.113.99'))
        ->and(UserDevice::fingerprint('Agent', '203.0.113.10'))->not->toBe(UserDevice::fingerprint('Agent', '198.51.100.10'))
        ->and(UserDevice::fingerprint('Agent', '2001:db8:1:2::1'))->toBe(UserDevice::fingerprint('Agent', '2001:db8:1:ffff::1'));
});
