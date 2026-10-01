<?php

use App\Domain\Tenancy\Enums\TenantRole;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Models\Workspace;
use App\Models\User;
use Database\Seeders\TenancySeeder;

it('seeds the ManeCMS spaces with one role of each kind', function (): void {
    $owner = User::factory()->create();
    $guest = User::factory()->create();

    asOwner(fn () => (new TenancySeeder)->run($owner, $guest));

    [$maneCms, $atlas] = asOwner(fn (): array => [
        Tenant::query()->where('name', 'ManeCMS')->with(['members', 'activeWorkspaces', 'workspaces'])->sole(),
        Tenant::query()->where('name', 'Studio Atlas')->with('members')->sole(),
    ]);

    expect($maneCms->members->firstWhere('user_id', $owner->id)?->role)->toBe(TenantRole::Owner)
        ->and($maneCms->members->firstWhere('user_id', $guest->id)?->role)->toBe(TenantRole::Member)
        ->and($atlas->members->firstWhere('user_id', $guest->id)?->role)->toBe(TenantRole::Owner)
        ->and($atlas->members->firstWhere('user_id', $owner->id)?->role)->toBe(TenantRole::Admin)
        ->and($maneCms->activeWorkspaces->pluck('name')->all())->toBe(['ManeCMS', 'Marketing', 'Site vitrine'])
        ->and($maneCms->workspaces->whereNotNull('archived_at')->pluck('name')->values()->all())->toBe(['Archives 2025']);
});

it('leaves the same data in place when it runs again', function (): void {
    $owner = User::factory()->create();
    $guest = User::factory()->create();

    asOwner(fn () => (new TenancySeeder)->run($owner, $guest));
    asOwner(fn () => (new TenancySeeder)->run($owner, $guest));

    expect(asOwner(fn (): array => [Tenant::query()->count(), Workspace::query()->count(), TenantMember::query()->count()]))
        ->toBe([2, 6, 4]);
});
