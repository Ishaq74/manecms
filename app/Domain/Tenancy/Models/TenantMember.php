<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Authorization\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\TenantMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

/**
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string $role_id
 * @property bool $is_owner
 * @property string|null $last_workspace_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Tenant $tenant
 * @property-read User $user
 * @property-read Role $role
 * @property-read Workspace|null $lastWorkspace
 */
#[Fillable(['user_id', 'role_id', 'last_workspace_id'])]
#[UseFactory(TenantMemberFactory::class)]
class TenantMember extends Model
{
    /** @use HasFactory<TenantMemberFactory> */
    use HasFactory, HasUlids;

    /**
     * `is_owner` is maintained by a database trigger from the role.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_owner' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function lastWorkspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'last_workspace_id');
    }

    /**
     * The workspaces the member is limited to; none means every workspace.
     *
     * @return BelongsToMany<Workspace, $this>
     */
    public function restrictedWorkspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'tenant_member_workspaces', 'tenant_member_id', 'workspace_id');
    }

    public function allowsWorkspace(string $workspaceId): bool
    {
        if ($this->is_owner) {
            return true;
        }

        $restrictions = DB::table('tenant_member_workspaces')->where('tenant_member_id', $this->id);

        return ! $restrictions->exists() || $restrictions->where('workspace_id', $workspaceId)->exists();
    }
}
