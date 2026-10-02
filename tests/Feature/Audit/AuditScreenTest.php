<?php

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Authorization\Enums\SystemRole;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Models\Workspace;
use Livewire\Livewire;

it('lets owners and admins open the audit of their space', function (SystemRole $role): void {
    [$user, $workspace] = joinWorkspace($role);

    $this->actingAs($user)->get(route('audit.index', $workspace))->assertOk();
})->with([SystemRole::Owner, SystemRole::Admin]);

it('forbids members from the audit', function (): void {
    [$user, $workspace] = joinWorkspace(SystemRole::Member);

    $this->actingAs($user)->get(route('audit.index', $workspace))->assertForbidden();
});

it('shows who renamed a workspace, when, what changed and the correlation', function (): void {
    [$owner, $workspace, $ownerMembership] = joinWorkspace(SystemRole::Owner);

    $admin = asOwner(function () use ($ownerMembership): TenantMember {
        $admin = TenantMember::factory()->admin()->create(['tenant_id' => $ownerMembership->tenant_id]);
        Workspace::factory()->for($ownerMembership->tenant)->create();

        return $admin;
    });

    enterWorkspace($admin, $workspace);
    $originalName = $workspace->name;

    Livewire::actingAs($admin->user)
        ->test('pages::workspaces.settings')
        ->set('name', 'Brand studio')
        ->call('renameWorkspace')
        ->assertHasNoErrors();

    $event = asOwner(fn () => AuditEvent::query()->where('action', 'tenancy.workspace.renamed')->sole());

    enterWorkspace($ownerMembership, $workspace);

    Livewire::actingAs($owner)
        ->test('pages::audit.index')
        ->assertSee($admin->user->name)
        ->assertSee('tenancy.workspace.renamed')
        ->assertSee($event->occurred_at->format('Y-m-d H:i:s'))
        ->call('toggleRow', $event->id)
        ->assertSee($originalName)
        ->assertSee('Brand studio')
        ->assertSee($event->correlation_id);
});

it('filters the audit by action and by correlation', function (): void {
    [$owner, $workspace, $member] = joinWorkspace(SystemRole::Owner);

    asOwner(function () use ($member): void {
        AuditEvent::factory()->create(['tenant_id' => $member->tenant_id, 'action' => 'tenancy.workspace.created', 'correlation_id' => 'first-correlation']);
        AuditEvent::factory()->create(['tenant_id' => $member->tenant_id, 'action' => 'tenancy.workspace.archived', 'correlation_id' => 'second-correlation']);
    });

    enterWorkspace($member, $workspace);

    Livewire::actingAs($owner)
        ->test('pages::audit.index')
        ->set('filters.action', 'tenancy.workspace.archived')
        ->assertSee('tenancy.workspace.archived')
        ->assertDontSee('tenancy.workspace.created</td>', escape: false)
        ->set('filters', [])
        ->set('search', 'first-correlation')
        ->assertSee('tenancy.workspace.created')
        ->assertDontSee('tenancy.workspace.archived</td>', escape: false);
});
