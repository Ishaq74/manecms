<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Policies\WorkspacePolicy;
use Carbon\CarbonImmutable;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property CarbonImmutable|null $archived_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Tenant $tenant
 */
#[Fillable(['name'])]
#[UseFactory(WorkspaceFactory::class)]
#[UsePolicy(WorkspacePolicy::class)]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /**
     * Active workspaces of active tenants the user belongs to, minus those a
     * workspace restriction hides from them.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function accessibleBy(Builder $query, int $userId): void
    {
        $restrictions = fn (QueryBuilder $rows): QueryBuilder => $rows->selectRaw('1')
            ->from('tenant_member_workspaces')
            ->whereColumn('tenant_member_workspaces.tenant_member_id', 'tenant_members.id');

        $query->whereNull('workspaces.archived_at')
            ->whereExists(fn (QueryBuilder $tenants): QueryBuilder => $tenants->selectRaw('1')
                ->from('tenants')
                ->whereColumn('tenants.id', 'workspaces.tenant_id')
                ->whereNull('tenants.archived_at'))
            ->whereExists(fn (QueryBuilder $members): QueryBuilder => $members->selectRaw('1')
                ->from('tenant_members')
                ->whereColumn('tenant_members.tenant_id', 'workspaces.tenant_id')
                ->where('tenant_members.user_id', $userId)
                ->where(fn (QueryBuilder $access): QueryBuilder => $access->where('tenant_members.is_owner', true)
                    ->orWhereNotExists($restrictions)
                    ->orWhereExists(fn (QueryBuilder $rows): QueryBuilder => $restrictions($rows)
                        ->whereColumn('tenant_member_workspaces.workspace_id', 'workspaces.id'))));
    }
}
