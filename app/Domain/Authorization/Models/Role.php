<?php

namespace App\Domain\Authorization\Models;

use App\Domain\Authorization\Enums\SystemRole;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantMember;
use Carbon\CarbonImmutable;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * A role of one tenant: either a system role (`system_key`) or a custom one.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $key
 * @property string $name
 * @property SystemRole|null $system_key
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Tenant $tenant
 */
#[Fillable(['key', 'name'])]
#[UseFactory(RoleFactory::class)]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'system_key' => SystemRole::class,
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
     * @return HasMany<TenantMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(TenantMember::class);
    }

    /**
     * System roles by rank, then custom roles by name.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderByRaw('system_key is null')
            ->orderByRaw("array_position(array['owner', 'admin', 'member']::varchar[], system_key)")
            ->orderBy('name');
    }

    /**
     * Roles a member can be given: ownership only moves by transfer.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function assignable(Builder $query): void
    {
        $query->where(fn (Builder $roles): Builder => $roles->whereNull('system_key')->orWhere('system_key', '!=', SystemRole::Owner->value));
    }

    public function isSystem(): bool
    {
        return $this->system_key !== null;
    }

    public function isSystemRole(SystemRole $role): bool
    {
        return $this->system_key === $role;
    }

    public function label(): string
    {
        return $this->system_key?->label() ?? $this->name;
    }

    /**
     * @return list<string>
     */
    public function permissionKeys(): array
    {
        $keys = DB::table('role_permissions')
            ->where('role_id', $this->id)
            ->orderBy('permission_key')
            ->pluck('permission_key')
            ->all();

        return array_values(array_filter($keys, is_string(...)));
    }

    public function grants(string $permissionKey): bool
    {
        return DB::table('role_permissions')
            ->where('role_id', $this->id)
            ->where('permission_key', $permissionKey)
            ->exists();
    }

    /**
     * Replace the permissions of the role.
     *
     * @param  list<string>  $permissionKeys
     */
    public function syncPermissions(array $permissionKeys): void
    {
        $permissionKeys = array_values(array_unique($permissionKeys));

        DB::table('role_permissions')
            ->where('role_id', $this->id)
            ->whereNotIn('permission_key', $permissionKeys)
            ->delete();

        DB::table('role_permissions')->insertOrIgnore(array_map(fn (string $key): array => [
            'tenant_id' => $this->tenant_id,
            'role_id' => $this->id,
            'permission_key' => $key,
        ], $permissionKeys));
    }
}
