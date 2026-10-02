<?php

use App\Domain\Authorization\Enums\SystemRole;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantMember;
use App\Models\User;
use Livewire\Livewire;

it('refuses to delete the account of a tenant owner', function (): void {
    [$user] = joinWorkspace(SystemRole::Owner);

    Livewire::actingAs($user)
        ->test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertHasErrors(['password']);

    expect(User::query()->whereKey($user->id)->exists())->toBeTrue();
    $this->assertAuthenticatedAs($user);
});

it('deletes a member account and removes only its membership', function (): void {
    [$user, , $member] = joinWorkspace(SystemRole::Member);

    Livewire::actingAs($user)
        ->test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertHasNoErrors()
        ->assertRedirect('/');

    asOwner(function () use ($user, $member): void {
        expect(User::query()->whereKey($user->id)->exists())->toBeFalse()
            ->and(TenantMember::query()->whereKey($member->id)->exists())->toBeFalse()
            ->and(Tenant::query()->whereKey($member->tenant_id)->exists())->toBeTrue();
    });
    $this->assertGuest();
});
