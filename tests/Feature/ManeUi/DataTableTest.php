<?php

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Platform\Models\SavedTableView;
use App\Domain\Tenancy\Enums\TenantRole;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Models\Workspace;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Feature\ManeUi\Fixtures\WorkspaceTable;

/**
 * An owner inside their workspace, with audit events of their tenant.
 *
 * @param  list<string>  $actions
 * @return array{0: User, 1: Workspace, 2: TenantMember}
 */
function auditTableFor(array $actions = []): array
{
    [$owner, $workspace, $member] = joinWorkspace(TenantRole::Owner);

    asOwner(function () use ($member, $actions): void {
        foreach ($actions as $action) {
            AuditEvent::factory()->create(['tenant_id' => $member->tenant_id, 'action' => $action]);
        }
    });

    enterWorkspace($member, $workspace);

    return [$owner, $workspace, $member];
}

function renderedRows(string $html): int
{
    return substr_count($html, 'data-test="data-table-row"');
}

it('ignores a sort on an undeclared column, from the URL or from the browser', function (): void {
    [$owner] = auditTableFor(['tenancy.workspace.created']);

    Livewire::withQueryParams(['sort' => 'ip', 'direction' => 'sideways'])
        ->actingAs($owner)
        ->test('pages::audit.index')
        ->assertSet('sort', '')
        ->assertSet('direction', 'desc')
        ->call('sortBy', 'user_agent')
        ->assertSet('sort', '')
        ->set('sort', 'actor_id desc; drop table audit_events')
        ->assertSet('sort', '')
        ->assertSee('tenancy.workspace.created');
});

it('sorts by a declared column in both directions', function (): void {
    [$owner] = auditTableFor(['b.second', 'a.first']);

    $component = Livewire::actingAs($owner)->test('pages::audit.index')->call('sortBy', 'action');

    expect(strpos($component->html(), 'a.first</td>'))->toBeLessThan(strpos($component->html(), 'b.second</td>'));

    $component->call('sortBy', 'action');

    expect(strpos($component->html(), 'b.second</td>'))->toBeLessThan(strpos($component->html(), 'a.first</td>'));
});

it('drops filters and page sizes that were not declared', function (): void {
    [$owner] = auditTableFor(array_fill(0, 12, 'tenancy.workspace.created'));

    $component = Livewire::actingAs($owner)
        ->test('pages::audit.index')
        ->set('filters', ['action' => 'not.an.action', 'ip' => '10.0.0.1'])
        ->assertSet('filters', [])
        ->set('perPage', 7)
        ->assertSet('perPage', 25)
        ->set('perPage', 10);

    expect(renderedRows($component->html()))->toBe(10);
});

it('searches literally, wildcards included', function (): void {
    [$owner] = auditTableFor(['tenancy.workspace.created']);

    Livewire::actingAs($owner)
        ->test('pages::audit.index')
        ->set('search', '%')
        ->assertSee('No result matches your search')
        ->set('search', 'workspace.cre')
        ->assertSee('tenancy.workspace.created</td>', escape: false);
});

it('never reads the table without a limit', function (): void {
    [$owner] = auditTableFor(['tenancy.workspace.created', 'tenancy.workspace.renamed']);

    $reads = [];
    DB::listen(function (QueryExecuted $query) use (&$reads): void {
        if (preg_match('/^select .* from "audit_events"/i', $query->sql) === 1) {
            $reads[] = $query->sql;
        }
    });

    Livewire::actingAs($owner)->test('pages::audit.index')->call('sortBy', 'action')->set('search', 'tenancy');

    expect($reads)->not->toBeEmpty()
        ->and(array_filter($reads, fn (string $sql): bool => ! str_contains(strtolower($sql), ' limit ')))->toBe([]);
});

it('runs a bulk action only on rows the table can see', function (): void {
    [$user, $workspace, $member] = joinWorkspace(TenantRole::Owner);
    $foreignWorkspace = asOwner(fn (): Workspace => Workspace::factory()->for(Tenant::factory())->create());

    enterWorkspace($member, $workspace);

    Livewire::actingAs($user)
        ->test(WorkspaceTable::class)
        ->set('selected', [$workspace->id, $foreignWorkspace->id])
        ->call('runBulkAction', 'archive')
        ->assertSet('archived', [$workspace->id])
        ->assertSet('selected', []);
});

it('refuses a bulk action that was not declared', function (): void {
    [$user, $workspace, $member] = joinWorkspace(TenantRole::Owner);
    enterWorkspace($member, $workspace);

    Livewire::actingAs($user)
        ->test(WorkspaceTable::class)
        ->set('selected', [$workspace->id])
        ->call('runBulkAction', 'delete-everything')
        ->assertStatus(404);
});

it('caps the selection at the bulk limit', function (): void {
    [$user, $workspace, $member] = joinWorkspace(TenantRole::Owner);
    enterWorkspace($member, $workspace);

    $component = Livewire::actingAs($user)
        ->test(WorkspaceTable::class)
        ->set('selected', array_map(fn (int $index): string => "row-{$index}", range(1, 150)));

    expect($component->get('selected'))->toHaveCount(100);
});

it('selects and clears the current page', function (): void {
    [$user, $workspace, $member] = joinWorkspace(TenantRole::Owner);
    enterWorkspace($member, $workspace);

    Livewire::actingAs($user)
        ->test(WorkspaceTable::class)
        ->call('togglePageSelection')
        ->assertSet('selected', [$workspace->id])
        ->call('togglePageSelection')
        ->assertSet('selected', []);
});

it('shows hidden columns on demand but never hides the last visible one', function (): void {
    [$user, $workspace, $member] = joinWorkspace(TenantRole::Owner);
    enterWorkspace($member, $workspace);

    Livewire::actingAs($user)
        ->test(WorkspaceTable::class)
        ->assertSet('hiddenColumns', ['created_at'])
        ->call('toggleColumn', 'created_at')
        ->assertSet('hiddenColumns', [])
        ->call('toggleColumn', 'name')
        ->assertSet('hiddenColumns', []);
});

it('saves a view and applies it again', function (): void {
    [$owner] = auditTableFor(['tenancy.workspace.created', 'tenancy.workspace.archived']);

    $component = Livewire::actingAs($owner)
        ->test('pages::audit.index')
        ->set('filters.action', 'tenancy.workspace.archived')
        ->set('density', 'dense')
        ->set('viewName', 'Archives')
        ->call('saveView')
        ->assertHasNoErrors();

    $viewId = (string) $component->get('activeView');

    $component->call('resetTable')
        ->assertSet('filters', [])
        ->assertSet('density', 'comfortable')
        ->call('applyView', $viewId)
        ->assertSet('filters', ['action' => 'tenancy.workspace.archived'])
        ->assertSet('density', 'dense')
        ->assertDontSee('tenancy.workspace.created</td>', escape: false);
});

it('keeps saved views private to their author, down to the database', function (): void {
    [$owner, $workspace, $ownerMembership] = auditTableFor(['tenancy.workspace.created']);

    $component = Livewire::actingAs($owner)
        ->test('pages::audit.index')
        ->set('viewName', 'Mine')
        ->call('saveView');

    $viewId = (string) $component->get('activeView');

    $admin = asOwner(fn (): TenantMember => TenantMember::factory()->admin()->create(['tenant_id' => $ownerMembership->tenant_id]));
    enterWorkspace($admin, $workspace);

    expect(SavedTableView::query()->whereKey($viewId)->exists())->toBeFalse();

    Livewire::actingAs($admin->user)
        ->test('pages::audit.index')
        ->assertDontSee('Mine')
        ->set('search', 'kept')
        ->call('applyView', $viewId)
        ->assertSet('search', 'kept')
        ->assertSet('activeView', null);
});

it('does not save a view without a name', function (): void {
    [$owner] = auditTableFor();

    Livewire::actingAs($owner)
        ->test('pages::audit.index')
        ->set('viewName', '   ')
        ->call('saveView')
        ->assertHasErrors(['viewName' => 'required']);
});
