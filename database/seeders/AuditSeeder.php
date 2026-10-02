<?php

namespace Database\Seeders;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Tenancy\Enums\TenantRole;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Audit history for the seeded spaces, so the audit screen has something to show.
 *
 * Idempotent: a tenant that already has its creation event is left untouched.
 */
class AuditSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::query()->with(['workspaces', 'members'])->each(function (Tenant $tenant): void {
            if (AuditEvent::query()->where('tenant_id', $tenant->id)->where('action', 'tenancy.tenant.created')->exists()) {
                return;
            }

            $ownerId = $tenant->members->firstWhere('role', TenantRole::Owner)?->user_id;
            $correlationId = (string) Str::ulid();

            $this->event($tenant, $ownerId, $correlationId, 'tenancy.tenant.created', $tenant, [], ['name' => $tenant->name]);

            $tenant->workspaces->each(function (Workspace $workspace) use ($tenant, $ownerId, $correlationId): void {
                $this->event($tenant, $ownerId, $correlationId, 'tenancy.workspace.created', $workspace, [], ['name' => $workspace->name]);
            });

            $renamed = $tenant->workspaces->firstWhere('archived_at', null);

            if ($renamed !== null) {
                $this->event($tenant, $ownerId, (string) Str::ulid(), 'tenancy.workspace.renamed', $renamed, ['name' => $renamed->name.' (brouillon)'], ['name' => $renamed->name]);
            }
        });
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
