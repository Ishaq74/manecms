<?php

use App\Domain\Tenancy\Enums\TenantRole;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Models\Workspace;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

it('rejects a second owner for the same tenant', function (): void {
    $tenant = Tenant::factory()->create();
    TenantMember::factory()->owner()->for($tenant)->create();

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

    expect(fn () => $member->update(['last_workspace_id' => $foreignWorkspace->id]))
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

    expect(Workspace::query()->where('name', 'Marketing')->count())->toBe(2);
});

it('removes the memberships of a deleted user', function (): void {
    $member = TenantMember::factory()->member()->create();

    User::query()->whereKey($member->user_id)->delete();

    expect(TenantMember::query()->whereKey($member->id)->exists())->toBeFalse()
        ->and(Tenant::query()->whereKey($member->tenant_id)->exists())->toBeTrue();
});

it('stores the role as a typed enum', function (): void {
    $member = TenantMember::factory()->owner()->create();

    expect($member->fresh()?->role)->toBe(TenantRole::Owner);
});
