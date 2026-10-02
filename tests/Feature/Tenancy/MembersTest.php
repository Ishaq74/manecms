<?php

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Authorization\Enums\SystemRole;
use App\Domain\Authorization\Errors\AuthorizationDenied;
use App\Domain\Authorization\Models\Role;
use App\Domain\Tenancy\Actions\LeaveTenant;
use App\Domain\Tenancy\Actions\RemoveMember;
use App\Domain\Tenancy\Actions\ResolveEntryWorkspace;
use App\Domain\Tenancy\Actions\RestrictMemberWorkspaces;
use App\Domain\Tenancy\Actions\UpdateMemberRole;
use App\Domain\Tenancy\Database\TenantDatabaseContext;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Models\Workspace;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function roleOf(TenantMember $member, SystemRole $role): Role
{
    return asOwner(fn () => $member->tenant->roles()->where('system_key', $role)->sole());
}

it('lets an admin change the role of a member, with an audit trail', function (): void {
    [$admin, $workspace, $adminMember] = joinWorkspace(SystemRole::Admin);
    $member = TenantMember::factory()->member()->for($adminMember->tenant)->create();
    enterWorkspace($adminMember, $workspace);

    app(UpdateMemberRole::class)($admin, $member->id, roleOf($adminMember, SystemRole::Admin)->id);

    expect(TenantMember::query()->with('role')->find($member->id)?->role->system_key)->toBe(SystemRole::Admin)
        ->and(AuditEvent::query()->where('action', 'tenancy.member.role_changed')->sole()->after)->toBe(['role' => 'admin']);
});

it('refuses role changes that escalate or touch protected members', function (Closure $target, ?SystemRole $newRole): void {
    [$admin, $workspace, $adminMember] = joinWorkspace(SystemRole::Admin);
    $owner = TenantMember::factory()->owner()->for($adminMember->tenant)->create();
    $peer = TenantMember::factory()->admin()->for($adminMember->tenant)->create();
    $member = TenantMember::factory()->member()->for($adminMember->tenant)->create();
    enterWorkspace($adminMember, $workspace);

    $targetId = $target(compact('owner', 'peer', 'member', 'adminMember'))->id;
    $roleId = roleOf($adminMember, $newRole ?? SystemRole::Member)->id;

    expect(fn () => app(UpdateMemberRole::class)($admin, $targetId, $roleId))->toThrow(AuthorizationDenied::class)
        ->and(AuditEvent::query()->where('action', 'authorization.denied')->count())->toBe(1);
})->with([
    'make someone owner' => [fn (array $members) => $members['member'], SystemRole::Owner],
    'demote the owner' => [fn (array $members) => $members['owner'], SystemRole::Member],
    'demote a peer admin' => [fn (array $members) => $members['peer'], SystemRole::Member],
    'change their own role' => [fn (array $members) => $members['adminMember'], SystemRole::Member],
]);

it('hides members and roles of other tenants', function (): void {
    [$owner, $workspace, $member] = joinWorkspace();
    $foreign = TenantMember::factory()->member()->create();
    enterWorkspace($member, $workspace);

    expect(fn () => app(RemoveMember::class)($owner, $foreign->id))->toThrow(ModelNotFoundException::class)
        ->and(fn () => app(UpdateMemberRole::class)($owner, $foreign->id, roleOf($member, SystemRole::Admin)->id))->toThrow(ModelNotFoundException::class)
        ->and(fn () => app(UpdateMemberRole::class)($owner, $foreign->id, roleOf($foreign, SystemRole::Admin)->id))->toThrow(ModelNotFoundException::class);
});

it('removes a member, who loses access at once', function (): void {
    [$owner, $workspace, $member] = joinWorkspace();
    $colleague = TenantMember::factory()->member()->for($member->tenant)->create();
    enterWorkspace($member, $workspace);

    app(RemoveMember::class)($owner, $colleague->id);
    app(TenantDatabaseContext::class)->clear();

    $this->actingAs($colleague->user)->get(route('workspace.home', $workspace))->assertNotFound();
    expect(asOwner(fn () => AuditEvent::query()->where('action', 'tenancy.member.removed')->exists()))->toBeTrue();
});

it('lets a member leave but keeps the owner until a transfer', function (): void {
    [$user, $workspace, $member] = joinWorkspace(SystemRole::Member);
    enterWorkspace($member, $workspace);

    app(LeaveTenant::class)($user);

    expect(asOwner(fn () => TenantMember::query()->whereKey($member->id)->exists()))->toBeFalse();

    [$owner, $ownerWorkspace, $ownerMember] = joinWorkspace();
    enterWorkspace($ownerMember, $ownerWorkspace);

    expect(fn () => app(LeaveTenant::class)($owner))->toThrow(ValidationException::class);
});

it('restricts a member to some workspaces on every route and on sign-in', function (): void {
    [$owner, $workspace, $ownerMember] = joinWorkspace();
    $marketing = Workspace::factory()->for($ownerMember->tenant)->create(['name' => 'Marketing']);
    $colleague = TenantMember::factory()->admin()->for($ownerMember->tenant)->create(['last_workspace_id' => $workspace->id]);
    enterWorkspace($ownerMember, $workspace);

    app(RestrictMemberWorkspaces::class)($owner, $colleague->id, [$marketing->id]);
    app(TenantDatabaseContext::class)->clear();

    $user = $colleague->user;

    $this->actingAs($user)->get(route('workspace.home', $workspace))->assertNotFound();
    $this->actingAs($user)->get(route('workspace.settings', $workspace))->assertNotFound();
    $this->actingAs($user)->get(route('workspace.home', $marketing))->assertOk();

    expect(app(ResolveEntryWorkspace::class)($user)?->id)->toBe($marketing->id);
});

it('shows only the reachable workspaces in the switcher', function (): void {
    [$owner, $workspace, $ownerMember] = joinWorkspace();
    $marketing = Workspace::factory()->for($ownerMember->tenant)->create(['name' => 'Marketing restreint']);
    $colleague = TenantMember::factory()->member()->for($ownerMember->tenant)->create();
    asOwner(fn () => $colleague->restrictedWorkspaces()->attach($marketing->id, ['tenant_id' => $colleague->tenant_id]));

    $this->actingAs($colleague->user)
        ->get(route('workspace.home', $marketing))
        ->assertOk()
        ->assertSee('Marketing restreint')
        ->assertDontSee($workspace->name);
});

it('applies restrictions to Livewire requests of a workspace', function (): void {
    [$owner, $workspace, $ownerMember] = joinWorkspace();
    $marketing = Workspace::factory()->for($ownerMember->tenant)->create();
    $colleague = TenantMember::factory()->admin()->for($ownerMember->tenant)->create();
    asOwner(fn () => $colleague->restrictedWorkspaces()->attach($marketing->id, ['tenant_id' => $colleague->tenant_id]));

    $this->actingAs($colleague->user)->get(route('workspace.settings', $workspace))->assertNotFound();
    $this->actingAs($colleague->user)->get(route('workspace.settings', $marketing))->assertOk();
});

it('validates restricted workspaces and never restricts the owner', function (): void {
    [$owner, $workspace, $ownerMember] = joinWorkspace();
    $colleague = TenantMember::factory()->member()->for($ownerMember->tenant)->create();
    $foreignWorkspace = Workspace::factory()->create();
    enterWorkspace($ownerMember, $workspace);

    expect(fn () => app(RestrictMemberWorkspaces::class)($owner, $colleague->id, [$foreignWorkspace->id]))->toThrow(ValidationException::class)
        ->and(fn () => app(RestrictMemberWorkspaces::class)($owner, $ownerMember->id, [$workspace->id]))->toThrow(AuthorizationDenied::class);
});

it('lets members see the members page but not manage anyone', function (): void {
    [$user, $workspace, $member] = joinWorkspace(SystemRole::Member);
    $colleague = TenantMember::factory()->member()->for($member->tenant)->create();
    enterWorkspace($member, $workspace);

    Livewire::actingAs($user)
        ->test('pages::members.index')
        ->assertSee($colleague->user->name)
        ->assertDontSee('data-test="manage-member-', false)
        ->call('manage', $colleague->id)
        ->assertForbidden();
});

it('changes a role and the workspace access from the members page', function (): void {
    [$owner, $workspace, $ownerMember] = joinWorkspace();
    $colleague = TenantMember::factory()->member()->for($ownerMember->tenant)->create();
    enterWorkspace($ownerMember, $workspace);

    Livewire::actingAs($owner)
        ->test('pages::members.index')
        ->call('manage', $colleague->id)
        ->assertSet('showManageModal', true)
        ->set('managedRoleId', roleOf($ownerMember, SystemRole::Admin)->id)
        ->call('saveRole')
        ->set('managedWorkspaceIds', [$workspace->id])
        ->call('saveRestriction')
        ->assertHasNoErrors();

    $fresh = asOwner(fn () => TenantMember::query()->with(['role', 'restrictedWorkspaces'])->find($colleague->id));

    expect($fresh?->role->system_key)->toBe(SystemRole::Admin)
        ->and($fresh?->restrictedWorkspaces->modelKeys())->toBe([$workspace->id]);
});

it('filters the members table by role', function (): void {
    [$owner, $workspace, $ownerMember] = joinWorkspace();
    $admin = TenantMember::factory()->admin()->for($ownerMember->tenant)->create();
    $member = TenantMember::factory()->member()->for($ownerMember->tenant)->create();
    enterWorkspace($ownerMember, $workspace);

    Livewire::actingAs($owner)
        ->test('pages::members.index')
        ->set('filters.role', roleOf($ownerMember, SystemRole::Admin)->id)
        ->assertSee($admin->user->email)
        ->assertDontSee($member->user->email);
});
