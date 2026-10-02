<?php

namespace Database\Seeders;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Platform\Models\SavedTableView;
use App\Domain\Tenancy\Enums\TenantRole;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Audit history for the seeded spaces, so the audit screen and its table have
 * something to page, sort, search and filter, plus a saved view per owner.
 *
 * Idempotent: each part checks its own marker before writing.
 */
class AuditSeeder extends Seeder
{
    public const int HISTORY_SIZE = 60;

    private const string HISTORY_PREFIX = 'seed-history-';

    public function run(): void
    {
        Tenant::query()->with(['workspaces', 'members'])->each(function (Tenant $tenant): void {
            $ownerId = $tenant->members->firstWhere('role', TenantRole::Owner)?->user_id;

            $this->seedCreation($tenant, $ownerId);
            $this->seedHistory($tenant, $ownerId);
            $this->seedSavedView($tenant, $ownerId);
        });
    }

    private function seedCreation(Tenant $tenant, ?int $ownerId): void
    {
        if (AuditEvent::query()->where('tenant_id', $tenant->id)->where('action', 'tenancy.tenant.created')->exists()) {
            return;
        }

        $correlationId = (string) Str::ulid();

        $this->event($tenant, $ownerId, $correlationId, 'tenancy.tenant.created', $tenant, [], ['name' => $tenant->name]);

        $tenant->workspaces->each(function (Workspace $workspace) use ($tenant, $ownerId, $correlationId): void {
            $this->event($tenant, $ownerId, $correlationId, 'tenancy.workspace.created', $workspace, [], ['name' => $workspace->name]);
        });

        $renamed = $tenant->workspaces->firstWhere('archived_at', null);

        if ($renamed !== null) {
            $this->event($tenant, $ownerId, (string) Str::ulid(), 'tenancy.workspace.renamed', $renamed, ['name' => $renamed->name.' (brouillon)'], ['name' => $renamed->name]);
        }
    }

    /**
     * Three months of activity, one event every seven hours, oldest first.
     */
    private function seedHistory(Tenant $tenant, ?int $ownerId): void
    {
        $workspace = $tenant->workspaces->first();

        if ($workspace === null || AuditEvent::query()->where('tenant_id', $tenant->id)->where('correlation_id', 'like', self::HISTORY_PREFIX.'%')->exists()) {
            return;
        }

        $sources = ['http', 'http', 'http', 'queue', 'cli'];

        for ($index = self::HISTORY_SIZE; $index >= 1; $index--) {
            $previous = "{$workspace->name} v{$index}";
            [$action, $subject, $before, $after] = match ($index % 4) {
                0 => ['tenancy.tenant.renamed', $tenant, ['name' => "{$tenant->name} ({$index})"], ['name' => $tenant->name]],
                1 => ['tenancy.workspace.created', $workspace, [], ['name' => $workspace->name]],
                default => ['tenancy.workspace.renamed', $workspace, ['name' => $previous], ['name' => $workspace->name]],
            };

            AuditEvent::query()->create([
                'tenant_id' => $tenant->id,
                'actor_id' => $ownerId,
                'action' => $action,
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->id,
                'before' => $before === [] ? null : $before,
                'after' => $after,
                'source' => $sources[$index % count($sources)],
                'ip' => '192.0.2.'.($index % 250 + 1),
                'user_agent' => 'ManeCMS seeder',
                'correlation_id' => self::HISTORY_PREFIX.$tenant->id.'-'.$index,
                'occurred_at' => now()->subHours($index * 7),
            ]);
        }
    }

    /**
     * A saved view of the audit table, so the views menu is not empty.
     */
    private function seedSavedView(Tenant $tenant, ?int $ownerId): void
    {
        if ($ownerId === null) {
            return;
        }

        SavedTableView::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'user_id' => $ownerId, 'table_key' => 'audit.events', 'name' => 'Renommages'],
            ['state' => [
                'search' => '',
                'sort' => 'occurred_at',
                'direction' => 'desc',
                'filters' => ['action' => 'tenancy.workspace.renamed'],
                'perPage' => 25,
                'hiddenColumns' => ['correlation_id'],
                'density' => 'compact',
            ]],
        );
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    private function event(Tenant $tenant, ?int $actorId, string $correlationId, string $action, Tenant|Workspace $subject, array $before, array $after): void
    {
        AuditEvent::query()->create([
            'tenant_id' => $tenant->id,
            'actor_id' => $actorId,
            'action' => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->id,
            'before' => $before === [] ? null : $before,
            'after' => $after === [] ? null : $after,
            'source' => 'cli',
            'correlation_id' => $correlationId,
            'occurred_at' => now(),
        ]);
    }
}
