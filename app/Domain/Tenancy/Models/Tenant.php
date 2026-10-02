<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Authorization\Models\Role;
use App\Domain\Tenancy\Policies\TenantPolicy;
use Carbon\CarbonImmutable;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $id
 * @property string $name
 * @property bool $require_mfa
 * @property CarbonImmutable|null $archived_at
 * @property CarbonImmutable|null $suspended_at
 * @property string|null $suspension_reason
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read TenantMember|null $owner
 */
#[Fillable(['name'])]
#[UseFactory(TenantFactory::class)]
#[UsePolicy(TenantPolicy::class)]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'require_mfa' => 'boolean',
            'archived_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Workspace, $this>
     */
    public function workspaces(): HasMany
    {
        return $this->hasMany(Workspace::class);
    }

    /**
     * @return HasMany<Workspace, $this>
     */
    public function activeWorkspaces(): HasMany
    {
        return $this->workspaces()->whereNull('archived_at')->orderBy('name');
    }

    /**
     * @return HasMany<TenantMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(TenantMember::class);
    }

    /**
     * @return HasOne<TenantMember, $this>
     */
    public function owner(): HasOne
    {
        return $this->hasOne(TenantMember::class)->where('is_owner', true);
    }

    /**
     * @return HasMany<Role, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    /**
     * @return HasMany<TenantInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TenantInvitation::class);
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
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
}
