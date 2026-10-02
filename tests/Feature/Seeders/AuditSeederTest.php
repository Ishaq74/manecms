<?php

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Platform\Models\SavedTableView;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Database\Seeders\AuditSeeder;
use Database\Seeders\TenancySeeder;

/**
 * @return array{0: int, 1: int}
 */
function seededAuditCounts(): array
{
    return asOwner(fn (): array => [AuditEvent::query()->count(), SavedTableView::query()->count()]);
}

it('gives every seeded space an audit history long enough to page through, and an owner view', function (): void {
    $owner = User::factory()->create();

    asOwner(function () use ($owner): void {
        (new TenancySeeder)->run($owner, User::factory()->create());
        (new AuditSeeder)->run();
    });

    $maneCms = asOwner(fn (): Tenant => Tenant::query()->where('name', 'ManeCMS')->sole());

    $history = asOwner(fn (): int => AuditEvent::query()->where('tenant_id', $maneCms->id)->count());
    $view = asOwner(fn (): SavedTableView => SavedTableView::query()->where('tenant_id', $maneCms->id)->sole());

    expect($history)->toBeGreaterThan(AuditSeeder::HISTORY_SIZE)
        ->and($view->user_id)->toBe($owner->id)
        ->and($view->table_key)->toBe('audit.events')
        ->and($view->state['filters'])->toBe(['action' => 'tenancy.workspace.renamed']);
});

it('adds nothing when it runs again', function (): void {
    asOwner(function (): void {
        (new TenancySeeder)->run(User::factory()->create(), User::factory()->create());
        (new AuditSeeder)->run();
    });

    $first = seededAuditCounts();

    asOwner(fn () => (new AuditSeeder)->run());

    expect(seededAuditCounts())->toBe($first);
});
