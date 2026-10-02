<?php

use App\Domain\Authorization\Enums\SystemRole;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantInvitation;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Models\Workspace;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/*
 * These invariants are enforced by constraints, independently of row level
 * security, so every statement runs as the table owner.
 */

it('rejects a second owner for the same tenant', function (): void {
    $tenant = asOwner(fn () => TenantMember::factory()->owner()->create()->tenant);

    expect(fn () => TenantMember::factory()->owner()->for($tenant)->create())
        ->toThrow(UniqueConstraintViolationException::class);
});

it('rejects a duplicate membership for the same user and tenant', function (): void {
    $member = TenantMember::factory()->member()->create();

    expect(fn () => TenantMember::factory()->admin()->create([
        'tenant_id' => $member->tenant_id,
        'user_id' => $member->user_id,
    ]))->toThrow(UniqueConstraintViolationException::class);
});

it('rejects a last workspace that belongs to another tenant', function (): void {
    $member = TenantMember::factory()->member()->create();
    $foreignWorkspace = Workspace::factory()->create();

    expect(fn () => asOwner(fn () => $member->update(['last_workspace_id' => $foreignWorkspace->id])))
        ->toThrow(fn (QueryException $exception) => expect($exception->getCode())->toBe('23503'));
});

it('rejects workspace names that differ only by case within a tenant', function (): void {
    $tenant = Tenant::factory()->create();
    Workspace::factory()->for($tenant)->create(['name' => 'Marketing']);

    expect(fn () => Workspace::factory()->for($tenant)->create(['name' => 'MARKETING']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('allows the same workspace name in two tenants', function (): void {
    Workspace::factory()->create(['name' => 'Marketing']);
    Workspace::factory()->create(['name' => 'Marketing']);

    expect(asOwner(fn (): int => Workspace::query()->where('name', 'Marketing')->count()))->toBe(2);
});

it('removes the memberships of a deleted user', function (): void {
    $member = TenantMember::factory()->member()->create();

    User::query()->whereKey($member->user_id)->delete();

    asOwner(function () use ($member): void {
        expect(TenantMember::query()->whereKey($member->id)->exists())->toBeFalse()
            ->and(Tenant::query()->whereKey($member->tenant_id)->exists())->toBeTrue();
    });
});

it('derives the owner flag from the owner system role', function (): void {
    $member = TenantMember::factory()->owner()->create();
    $fresh = asOwner(fn () => $member->fresh()?->load('role'));

    expect($fresh?->role->system_key)->toBe(SystemRole::Owner)
        ->and($fresh?->is_owner)->toBeTrue();
});

it('keeps one pending invitation per email and tenant, but allows a new one once the previous is closed', function (): void {
    $invitation = TenantInvitation::factory()->create(['email' => 'lea@example.test']);
    $tenant = asOwner(fn () => $invitation->tenant);

    expect(fn () => TenantInvitation::factory()->for($tenant)->create(['email' => 'lea@example.test']))
        ->toThrow(UniqueConstraintViolationException::class);

    asOwner(fn () => $invitation->forceFill(['revoked_at' => now()])->save());
    TenantInvitation::factory()->for($tenant)->create(['email' => 'lea@example.test']);

    expect(asOwner(fn (): int => TenantInvitation::query()->where('email', 'lea@example.test')->count()))->toBe(2);
});

it('rejects an invitation email that is not normalised, or both accepted and revoked', function (array $attributes): void {
    expect(fn () => TenantInvitation::factory()->create($attributes))
        ->toThrow(fn (QueryException $exception) => expect($exception->getCode())->toBe('23514'));
})->with([
    'upper case' => [['email' => 'Lea@example.test']],
    'surrounding spaces' => [['email' => ' lea@example.test']],
    'two outcomes' => [['accepted_at' => now(), 'revoked_at' => now()]],
]);

it('rejects a workspace restriction pointing to another tenant', function (): void {
    $member = TenantMember::factory()->member()->create();
    $foreignWorkspace = Workspace::factory()->create();

    expect(fn () => asOwner(fn () => $member->restrictedWorkspaces()->attach($foreignWorkspace->id, ['tenant_id' => $member->tenant_id])))
        ->toThrow(fn (QueryException $exception) => expect($exception->getCode())->toBe('23503'));
});

it('rejects a malformed permission key', function (): void {
    expect(fn () => asOwner(fn () => DB::table('permissions')->insert(['key' => 'Tenancy.Member', 'context' => 'tenancy', 'capability' => 'safe'])))
        ->toThrow(fn (QueryException $exception) => expect($exception->getCode())->toBe('23514'));
});
