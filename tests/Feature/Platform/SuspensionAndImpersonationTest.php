<?php

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Authorization\Enums\DenialReason;
use App\Domain\Authorization\PolicyEngine;
use App\Domain\Platform\Actions\SetTenantSuspension;
use App\Domain\Platform\ImpersonationSession;
use App\Domain\Platform\Models\Impersonation;
use App\Domain\Tenancy\Database\TenantDatabaseContext;
use App\Domain\Tenancy\Exceptions\TenantSuspended;
use App\Domain\Tenancy\Jobs\Middleware\RunInTenantContext;
use App\Domain\Tenancy\Jobs\TenantScopedJob;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Permissions\TenancyPermission;
use App\Domain\Identity\Notifications\NewSignInNotification;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/**
 * An operator with 2FA and a freshly confirmed password, signed in.
 */
function signInOperator(): User
{
    $operator = User::factory()->operator()->withTwoFactor()->create();

    test()->actingAs($operator)->withSession(['auth.password_confirmed_at' => time()]);
    // Livewire::test() mounts without the web middleware that scopes the database session.
    app(TenantDatabaseContext::class)->apply(null, $operator->id);

    return $operator;
}

function suspend(User $operator, Tenant $tenant, string $reason = 'Impayé depuis trois mois'): void
{
    app(TenantDatabaseContext::class)->runAs(null, $operator->id, fn () => app(SetTenantSuspension::class)->suspend($operator, $tenant->id, $reason));
}

it('suspends a space: members read the reason, Livewire and the Policy Engine refuse, jobs fail', function (): void {
    [$user, $workspace, $member] = joinWorkspace();
    $operator = User::factory()->operator()->create();

    suspend($operator, $member->tenant);

    $this->actingAs($user)
        ->get(route('workspace.home', $workspace))
        ->assertForbidden()
        ->assertSee('Impayé depuis trois mois');

    $this->actingAs($user)
        ->withHeader('X-Livewire', 'true')
        ->getJson(route('members.index', $workspace))
        ->assertForbidden()
        ->assertJsonPath('code', TenantSuspended::CODE);

    $tenant = asOwner(fn () => Tenant::query()->find($member->tenant_id));
    enterWorkspace($member->setRelation('tenant', $tenant), $workspace);

    expect(app(PolicyEngine::class)->decide($user, TenancyPermission::MemberView)->reason)->toBe(DenialReason::TenantSuspended);

    $job = new class($member->tenant_id) implements TenantScopedJob
    {
        public function __construct(private string $tenant) {}

        public function tenantId(): string
        {
            return $this->tenant;
        }
    };

    expect(fn () => (new RunInTenantContext)->handle($job, fn (): bool => true))->toThrow(TenantSuspended::class)
        ->and(platformAudit('platform.tenant.suspended')?->after)->toBe(['reason' => 'Impayé depuis trois mois']);
});

it('reactivates a suspended space', function (): void {
    [$user, $workspace, $member] = joinWorkspace();
    $operator = User::factory()->operator()->create();
    suspend($operator, $member->tenant);

    app(TenantDatabaseContext::class)->runAs(null, $operator->id, fn () => app(SetTenantSuspension::class)->reactivate($operator, $member->tenant_id));

    $this->actingAs($user)->get(route('workspace.home', $workspace))->assertOk();

    expect(platformAudit('platform.tenant.reactivated'))->not->toBeNull();
});

it('requires a reason and an operator to suspend', function (): void {
    $tenant = Tenant::factory()->create();
    $operator = User::factory()->operator()->create();
    $user = User::factory()->create();

    expect(fn () => suspend($operator, $tenant, 'court'))->toThrow(ValidationException::class)
        ->and(fn () => suspend($user, $tenant))->toThrow(Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
});

it('lists, opens and suspends a space from the back-office', function (): void {
    $member = TenantMember::factory()->owner()->create();
    $tenant = asOwner(fn () => $member->tenant);
    signInOperator();

    $this->get(route('platform.tenants.index'))->assertOk()->assertSee($tenant->name);
    $this->get(route('platform.tenants.show', $tenant->id))->assertOk()->assertSee($member->user->email);
    $this->get(route('platform.tenants.show', (string) Str::ulid()))->assertNotFound();

    Livewire::test('pages::platform.tenant', ['tenant' => $tenant->id])
        ->set('suspensionReason', 'Fraude signalée par le support')
        ->call('suspend')
        ->assertHasNoErrors()
        ->assertSee('Fraude signalée par le support');

    expect(asOwner(fn () => Tenant::query()->find($tenant->id)?->isSuspended()))->toBeTrue();
});

it('shows the platform audit to operators', function (): void {
    signInOperator();
    asOwner(fn () => AuditEvent::factory()->create(['tenant_id' => null, 'action' => 'platform.tenant.suspended']));

    $this->get(route('platform.audit'))->assertOk()->assertSee('platform.tenant.suspended');
});

it('starts a support session: read only, visible, audited, without a new-device email', function (): void {
    Notification::fake();
    $operator = signInOperator();
    $member = TenantMember::factory()->member()->create();
    $user = $member->user;

    Livewire::test('pages::platform.tenant', ['tenant' => $member->tenant_id])
        ->call('prepareImpersonation', $user->id)
        ->set('impersonationReason', 'Ticket 4521 : écran vide')
        ->set('impersonationMinutes', '15')
        ->call('impersonate')
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);

    $impersonation = Impersonation::query()->sole();

    expect($impersonation->operator_id)->toBe($operator->id)
        ->and($impersonation->expires_at->diffInMinutes($impersonation->started_at, true))->toBe(15.0)
        ->and(platformAudit('platform.impersonation.started')?->actor_id)->toBe($operator->id);

    Notification::assertNothingSent();
});

it('refuses writes during a support session and audits them', function (): void {
    [$user, $workspace, $member] = joinWorkspace();
    $operator = User::factory()->operator()->create();
    $impersonation = Impersonation::factory()->create(['operator_id' => $operator->id, 'user_id' => $user->id]);

    $this->actingAs($user)
        ->withSession([ImpersonationSession::SESSION_KEY => $impersonation->id])
        ->get(route('workspace.home', $workspace))
        ->assertOk()
        ->assertSee('data-test="impersonation-banner"', false);

    $this->post(route('logout'))->assertRedirect();

    $this->actingAs($user)
        ->withSession([ImpersonationSession::SESSION_KEY => $impersonation->id])
        ->post(route('two-factor.enable'))
        ->assertForbidden();

    session()->put(ImpersonationSession::SESSION_KEY, $impersonation->id);
    enterWorkspace($member, $workspace);

    Livewire::actingAs($user)
        ->test('pages::members.index')
        ->call('openInvite')
        ->assertForbidden();

    expect(asOwner(fn (): int => AuditEvent::query()->where('action', 'platform.impersonation.blocked')->count()))->toBe(2);
});

it('lets table navigation through during a support session', function (): void {
    [$user, $workspace, $member] = joinWorkspace();
    $impersonation = Impersonation::factory()->create(['user_id' => $user->id, 'operator_id' => User::factory()->operator()]);
    session()->put(ImpersonationSession::SESSION_KEY, $impersonation->id);
    enterWorkspace($member, $workspace);

    Livewire::actingAs($user)
        ->test('pages::members.index')
        ->call('sortBy', 'users.email')
        ->assertOk();
});

it('ends an expired support session and gives the session back to the operator', function (): void {
    [$user, $workspace] = joinWorkspace();
    $operator = User::factory()->operator()->withTwoFactor()->create();
    $impersonation = Impersonation::factory()->create(['operator_id' => $operator->id, 'user_id' => $user->id]);

    $this->travel(16)->minutes();

    $this->actingAs($user)
        ->withSession([ImpersonationSession::SESSION_KEY => $impersonation->id])
        ->get(route('workspace.home', $workspace))
        ->assertRedirect(route('platform.dashboard'));

    $this->assertAuthenticatedAs($operator);

    expect($impersonation->fresh()?->end_reason)->toBe(Impersonation::ENDED_EXPIRED)
        ->and(platformAudit('platform.impersonation.expired'))->not->toBeNull();
});

it('ends a support session on request', function (): void {
    [$user] = joinWorkspace();
    $operator = User::factory()->operator()->withTwoFactor()->create();
    $impersonation = Impersonation::factory()->create(['operator_id' => $operator->id, 'user_id' => $user->id]);

    $this->actingAs($user)
        ->withSession([ImpersonationSession::SESSION_KEY => $impersonation->id])
        ->post(route('impersonation.stop'))
        ->assertRedirect(route('platform.dashboard'));

    $this->assertAuthenticatedAs($operator);

    expect($impersonation->fresh()?->end_reason)->toBe(Impersonation::ENDED_STOPPED);
});

it('never impersonates an operator, oneself, or for too long, and requires a reason', function (Closure $target, string $reason, int $minutes): void {
    $operator = signInOperator();

    expect(fn () => app(ImpersonationSession::class)->start($operator, $target($operator)->id, $reason, $minutes))
        ->toThrow(ValidationException::class)
        ->and(Impersonation::query()->count())->toBe(0);
})->with([
    'another operator' => [fn () => User::factory()->operator()->create(), 'Ticket 1234 : test', 15],
    'oneself' => [fn (User $operator) => $operator, 'Ticket 1234 : test', 15],
    'unverified account' => [fn () => User::factory()->unverified()->create(), 'Ticket 1234 : test', 15],
    'no reason' => [fn () => User::factory()->create(), '   ', 15],
    'too long' => [fn () => User::factory()->create(), 'Ticket 1234 : test', 60],
]);

it('caps a support session at 30 minutes in the database', function (): void {
    expect(fn () => Impersonation::factory()->create(['expires_at' => now()->addMinutes(31)]))
        ->toThrow(Illuminate\Database\QueryException::class);
});
