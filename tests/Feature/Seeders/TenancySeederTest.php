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

    (new TenancySeeder)->run($owner, $guest);

    $maneCms = Tenant::query()->where('name', 'ManeCMS')->sole();
    $atlas = Tenant::query()->where('name', 'Studio Atlas')->sole();

    expect($maneCms->members()->where('user_id', $owner->id)->sole()->role)->toBe(TenantRole::Owner)
        ->and($maneCms->members()->where('user_id', $guest->id)->sole()->role)->toBe(TenantRole::Member)
        ->and($atlas->members()->where('user_id', $guest->id)->sole()->role)->toBe(TenantRole::Owner)
        ->and($atlas->members()->where('user_id', $owner->id)->sole()->role)->toBe(TenantRole::Admin)
        ->and($maneCms->activeWorkspaces()->pluck('name')->all())->toBe(['ManeCMS', 'Marketing', 'Site vitrine'])
        ->and($maneCms->workspaces()->whereNotNull('archived_at')->pluck('name')->all())->toBe(['Archives 2025']);
});

it('leaves the same data in place when it runs again', function (): void {
    $owner = User::factory()->create();
    $guest = User::factory()->create();

    (new TenancySeeder)->run($owner, $guest);
    (new TenancySeeder)->run($owner, $guest);

    expect(Tenant::query()->count())->toBe(2)
        ->and(Workspace::query()->count())->toBe(6)
        ->and(TenantMember::query()->count())->toBe(4);
});
