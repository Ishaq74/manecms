<?php

use App\Domain\Tenancy\Enums\TenantRole;
use Livewire\Livewire;

it('summarises validation errors and keeps the field values', function (): void {
    [$user, $workspace, $member] = joinWorkspace(TenantRole::Owner);
    enterWorkspace($member, $workspace);

    Livewire::actingAs($user)
        ->test('pages::workspaces.settings')
        ->set('name', '')
        ->call('renameWorkspace')
        ->assertHasErrors('name')
        ->assertSeeHtml('data-test="form-error-summary"');
});

it('discards unsaved changes and errors on reset', function (): void {
    [$user, $workspace, $member] = joinWorkspace(TenantRole::Owner);
    enterWorkspace($member, $workspace);

    Livewire::actingAs($user)
        ->test('pages::workspaces.settings')
        ->set('name', '')
        ->call('renameWorkspace')
        ->assertHasErrors('name')
        ->call('resetForm')
        ->assertHasNoErrors()
        ->assertSet('name', $workspace->name)
        ->assertDontSeeHtml('data-test="form-error-summary"');
});

it('flags unsaved changes and busy submissions in the form markup', function (): void {
    [$user, $workspace, $member] = joinWorkspace(TenantRole::Owner);
    enterWorkspace($member, $workspace);

    Livewire::actingAs($user)
        ->test('pages::tenants.settings')
        ->assertSeeHtml('wire:dirty')
        ->assertSeeHtml('wire:loading.attr="aria-busy"')
        ->assertSeeHtml('wire:target="renameTenant"');
});
