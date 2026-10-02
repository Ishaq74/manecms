<?php

use App\Domain\Authorization\Enums\SystemRole;
use App\Domain\Identity\Models\UserDevice;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantInvitation;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Models\Workspace;
use App\Models\User;
use Database\Seeders\IdentitySeeder;
use Database\Seeders\TenancySeeder;

it('seeds the ManeCMS spaces with one role of each kind', function (): void {
    $owner = User::factory()->create();
    $guest = User::factory()->create();

    asOwner(fn () => (new TenancySeeder)->run($owner, $guest));

    [$maneCms, $atlas] = asOwner(fn (): array => [
        Tenant::query()->where('name', 'ManeCMS')->with(['members.role', 'activeWorkspaces', 'workspaces'])->sole(),
        Tenant::query()->where('name', 'Studio Atlas')->with('members.role')->sole(),
    ]);

    expect($maneCms->members->firstWhere('user_id', $owner->id)?->role->system_key)->toBe(SystemRole::Owner)
        ->and($maneCms->members->firstWhere('user_id', $guest->id)?->role->system_key)->toBe(SystemRole::Member)
        ->and($atlas->members->firstWhere('user_id', $guest->id)?->role->system_key)->toBe(SystemRole::Owner)
        ->and($atlas->members->firstWhere('user_id', $owner->id)?->role->system_key)->toBe(SystemRole::Admin)
        ->and($maneCms->activeWorkspaces->pluck('name')->all())->toBe(['ManeCMS', 'Marketing', 'Site vitrine'])
        ->and($maneCms->workspaces->whereNotNull('archived_at')->pluck('name')->values()->all())->toBe(['Archives 2025']);
});

it('seeds custom roles, a restricted member and invitations in every state', function (): void {
    asOwner(fn () => (new TenancySeeder)->run(User::factory()->create(), User::factory()->create()));

    $maneCms = asOwner(fn (): Tenant => Tenant::query()->where('name', 'ManeCMS')->with(['roles', 'invitations', 'members.restrictedWorkspaces', 'members.user'])->sole());
    $camille = $maneCms->members->first(fn (TenantMember $member): bool => $member->user->email === 'camille@manecms.test');

    expect($maneCms->roles->whereNull('system_key')->pluck('name')->sort()->values()->all())->toBe(['Auditeur', 'Éditeur'])
        ->and($camille?->restrictedWorkspaces->pluck('name')->sort()->values()->all())->toBe(['Marketing', 'Site vitrine'])
        ->and($maneCms->invitations->map(fn (TenantInvitation $invitation): string => $invitation->status()->value)->sort()->values()->all())
        ->toBe(['expired', 'pending', 'revoked']);
});

it('leaves the same data in place when it runs again', function (): void {
    $owner = User::factory()->create();
    $guest = User::factory()->create();

    asOwner(fn () => (new TenancySeeder)->run($owner, $guest));
    asOwner(fn () => (new TenancySeeder)->run($owner, $guest));

    expect(asOwner(fn (): array => [Tenant::query()->count(), Workspace::query()->count(), TenantMember::query()->count()]))
        ->toBe([2, 6, 7]);
});

it('records known devices of the local account once', function (): void {
    $user = User::factory()->create();

    asOwner(fn () => (new IdentitySeeder)->run($user));
    asOwner(fn () => (new IdentitySeeder)->run($user));

    expect(UserDevice::query()->where('user_id', $user->id)->count())->toBe(3);
});
