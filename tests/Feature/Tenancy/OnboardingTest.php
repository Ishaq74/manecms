<?php

use App\Domain\Authorization\Enums\SystemRole;
use App\Domain\Tenancy\Actions\CreateTenant;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Models\Workspace;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

it('redirects a user without a tenant from the dashboard to the onboarding', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertRedirect(route('onboarding'));
});

it('creates the tenant, its first workspace and the owner membership together', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test('pages::onboarding.tenant')
        ->set('name', '  Acme    Studio ')
        ->call('createTenant')
        ->assertHasNoErrors();

    [$tenant, $workspace, $member] = asOwner(fn (): array => [
        Tenant::query()->sole(),
        Workspace::query()->sole(),
        TenantMember::query()->with('role')->sole(),
    ]);

    expect($tenant->name)->toBe('Acme Studio')
        ->and($workspace->name)->toBe('Acme Studio')
        ->and($workspace->tenant_id)->toBe($tenant->id)
        ->and($member->user_id)->toBe($user->id)
        ->and($member->role->system_key)->toBe(SystemRole::Owner)
        ->and($member->last_workspace_id)->toBe($workspace->id);

    $component->assertRedirect(route('workspace.home', $workspace));
});

it('writes nothing when the creation fails halfway', function (): void {
    Workspace::creating(fn (): never => throw new RuntimeException('Simulated failure'));

    expect(fn () => app(CreateTenant::class)(User::factory()->create(), 'Acme'))
        ->toThrow(RuntimeException::class, 'Simulated failure');

    expect(asOwner(fn (): int => Tenant::query()->count()))->toBe(0)
        ->and(asOwner(fn (): int => TenantMember::query()->count()))->toBe(0);
});

it('rejects names that are empty, too short or too long once spaces are normalised', function (string $name): void {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::onboarding.tenant')
        ->set('name', $name)
        ->call('createTenant')
        ->assertHasErrors(['name']);

    expect(asOwner(fn (): int => Tenant::query()->count()))->toBe(0);
})->with([
    'empty' => '',
    'spaces only' => '     ',
    'one character after squish' => '  a  ',
    'longer than 120 characters' => str_repeat('a', 121),
]);

it('limits a user to five new tenants per hour', function (): void {
    $user = User::factory()->create();
    $createTenant = app(CreateTenant::class);

    foreach (range(1, 5) as $attempt) {
        $createTenant($user, "Space {$attempt}");
    }

    expect(fn () => $createTenant($user, 'Space 6'))->toThrow(ValidationException::class)
        ->and(asOwner(fn (): int => Tenant::query()->count()))->toBe(5);
});
