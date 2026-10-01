<?php

use App\Domain\Tenancy\Actions\ArchiveWorkspace;
use App\Domain\Tenancy\Actions\RenameWorkspace;
use App\Domain\Tenancy\Enums\TenantRole;
use App\Domain\Tenancy\Models\Workspace;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

dataset('managers', [TenantRole::Owner, TenantRole::Admin]);

it('lets owners and admins create a workspace', function (TenantRole $role): void {
    [$user, $workspace, $member] = joinWorkspace($role);
    enterWorkspace($member, $workspace);

    Livewire::actingAs($user)
        ->test('pages::workspaces.create')
        ->set('name', 'Design')
        ->call('createWorkspace')
        ->assertHasNoErrors();

    expect($member->tenant->workspaces()->where('name', 'Design')->exists())->toBeTrue();
})->with('managers');

it('lets owners and admins rename and archive a workspace', function (TenantRole $role): void {
    [$user, $workspace, $member] = joinWorkspace($role);
    $second = Workspace::factory()->for($member->tenant)->create();
    enterWorkspace($member, $second);

    Livewire::actingAs($user)
        ->test('pages::workspaces.settings')
        ->set('name', 'Renamed')
        ->call('renameWorkspace')
        ->assertHasNoErrors()
        ->call('archiveWorkspace')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    expect($second->fresh()?->name)->toBe('Renamed')
        ->and($second->fresh()?->isArchived())->toBeTrue()
        ->and($workspace->fresh()?->isArchived())->toBeFalse();
})->with('managers');

it('forbids members from the workspace management pages', function (string $routeName): void {
    [$user, $workspace] = joinWorkspace(TenantRole::Member);

    $this->actingAs($user)
        ->get(route($routeName, $workspace))
        ->assertForbidden();
})->with(['workspace.create', 'workspace.settings', 'tenant.settings']);

it('forbids members from renaming or archiving a workspace', function (): void {
    [$user, $workspace, $member] = joinWorkspace(TenantRole::Member);
    Workspace::factory()->for($member->tenant)->create();
    enterWorkspace($member, $workspace);

    expect(fn () => app(RenameWorkspace::class)($user, $workspace->id, 'Renamed'))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => app(ArchiveWorkspace::class)($user, $workspace->id))
        ->toThrow(AuthorizationException::class)
        ->and($workspace->fresh()?->name)->not->toBe('Renamed')
        ->and($workspace->fresh()?->isArchived())->toBeFalse();
});

it('rejects a workspace name already used in the tenant, ignoring case and including archived ones', function (): void {
    [$user, $workspace, $member] = joinWorkspace();
    Workspace::factory()->for($member->tenant)->archived()->create(['name' => 'Design']);
    enterWorkspace($member, $workspace);

    Livewire::actingAs($user)
        ->test('pages::workspaces.create')
        ->set('name', 'DESIGN')
        ->call('createWorkspace')
        ->assertHasErrors(['name']);

    expect($member->tenant->workspaces()->count())->toBe(2);
});

it('refuses to archive the last active workspace', function (): void {
    [$user, $workspace, $member] = joinWorkspace();
    enterWorkspace($member, $workspace);

    Livewire::actingAs($user)
        ->test('pages::workspaces.settings')
        ->call('archiveWorkspace')
        ->assertHasErrors(['workspace']);

    expect($workspace->fresh()?->isArchived())->toBeFalse();
});

it('forgets an archived workspace as the last one used', function (): void {
    [$user, $workspace, $member] = joinWorkspace();
    Workspace::factory()->for($member->tenant)->create();
    $member->update(['last_workspace_id' => $workspace->id]);
    enterWorkspace($member, $workspace);

    app(ArchiveWorkspace::class)($user, $workspace->id);

    expect($member->fresh()?->last_workspace_id)->toBeNull();
});

it('never reaches a workspace of another tenant through a forged identifier', function (): void {
    [$user, $workspace, $member] = joinWorkspace();
    [, $foreign] = joinWorkspace();
    enterWorkspace($member, $workspace);

    expect(fn () => app(RenameWorkspace::class)($user, $foreign->id, 'Hijacked'))
        ->toThrow(ModelNotFoundException::class)
        ->and(fn () => app(ArchiveWorkspace::class)($user, $foreign->id))
        ->toThrow(ModelNotFoundException::class)
        ->and($foreign->fresh()?->name)->not->toBe('Hijacked')
        ->and($foreign->fresh()?->isArchived())->toBeFalse();
});

it('lets owners and admins rename the tenant', function (TenantRole $role): void {
    [$user, $workspace, $member] = joinWorkspace($role);
    enterWorkspace($member, $workspace);

    Livewire::actingAs($user)
        ->test('pages::tenants.settings')
        ->set('name', ' New   name ')
        ->call('renameTenant')
        ->assertHasNoErrors();

    expect($member->tenant->fresh()?->name)->toBe('New name');
})->with('managers');
