<?php

use App\Domain\Platform\Enums\PlatformRole;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('grants and revokes the platform role only through the commands, with an audit trail', function (): void {
    $user = User::factory()->create(['email' => 'ops@example.test']);

    expect(asOwner(fn (): int => Artisan::call('platform:grant-operator', ['email' => 'OPS@example.test'])))->toBe(0)
        ->and($user->fresh()?->platform_role)->toBe(PlatformRole::Operator)
        ->and(platformAudit('platform.operator.granted')?->subject_id)->toBe((string) $user->id);

    expect(asOwner(fn (): int => Artisan::call('platform:revoke-operator', ['email' => 'ops@example.test'])))->toBe(0)
        ->and($user->fresh()?->platform_role)->toBeNull()
        ->and(platformAudit('platform.operator.revoked'))->not->toBeNull();
});

it('fails on an unknown email', function (): void {
    expect(asOwner(fn (): int => Artisan::call('platform:grant-operator', ['email' => 'nobody@example.test'])))->toBe(1);
});

it('refuses any other way to become an operator, even in SQL', function (): void {
    $user = User::factory()->create();

    expect(fn () => DB::table('users')->where('id', $user->id)->update(['platform_role' => 'operator']))
        ->toThrow(QueryException::class)
        ->and(fn () => $user->forceFill(['platform_role' => PlatformRole::Operator])->save())
        ->toThrow(QueryException::class);
});

it('answers 404 on every back-office page to guests and non-operators', function (string $route): void {
    $tenantId = (string) Str::ulid();
    $url = $route === 'platform.tenants.show' ? route($route, $tenantId) : route($route);

    $this->get($url)->assertNotFound();
    $this->actingAs(User::factory()->withTwoFactor()->create())->get($url)->assertNotFound();
})->with(['platform.dashboard', 'platform.tenants.index', 'platform.tenants.show', 'platform.audit']);

it('sends an operator without two-factor authentication to the security settings', function (): void {
    $this->actingAs(User::factory()->operator()->create())
        ->get(route('platform.dashboard'))
        ->assertRedirect(route('security.edit', ['required' => 'mfa']));
});

it('asks an operator to confirm the password before the back-office', function (): void {
    $this->actingAs(User::factory()->operator()->withTwoFactor()->create())
        ->get(route('platform.dashboard'))
        ->assertRedirect(route('password.confirm'));
});

it('shows the health checks to a confirmed operator', function (): void {
    $this->actingAs(User::factory()->operator()->withTwoFactor()->create())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('platform.dashboard'))
        ->assertOk()
        ->assertSee('data-test="health-checks"', false)
        ->assertSee(__('Database'))
        ->assertSee(__('Failed jobs'));
});

it('lets the platform views return rows to operators only', function (): void {
    App\Domain\Tenancy\Models\TenantMember::factory()->owner()->create();
    $operator = User::factory()->operator()->create();
    $user = User::factory()->create();
    $database = app(App\Domain\Tenancy\Database\TenantDatabaseContext::class);

    $count = fn (int $userId, string $view): int => $database->runAs(null, $userId, fn (): int => DB::table($view)->count());

    expect($count($user->id, 'platform_tenants'))->toBe(0)
        ->and($count($user->id, 'platform_tenant_members'))->toBe(0)
        ->and($count($operator->id, 'platform_tenants'))->toBe(1)
        ->and($count($operator->id, 'platform_tenant_members'))->toBe(1)
        ->and(fn () => DB::table('platform_tenants')->delete())->toThrow(QueryException::class);
});
