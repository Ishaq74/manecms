<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Enums\TenantRole;
use App\Models\User;
use Database\Factories\TenantMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property TenantRole $role
 * @property string|null $last_workspace_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Tenant $tenant
 * @property-read User $user
 * @property-read Workspace|null $lastWorkspace
 */
#[Fillable(['user_id', 'role', 'last_workspace_id'])]
#[UseFactory(TenantMemberFactory::class)]
class TenantMember extends Model
{
    /** @use HasFactory<TenantMemberFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => TenantRole::class,
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
     * @return BelongsTo<Workspace, $this>
     */
    public function lastWorkspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'last_workspace_id');
    }
}
