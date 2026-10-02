<?php

namespace App\Domain\Platform\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A tenant as the operator back-office sees it: the `platform_tenants` view, empty for non-operators.
 *
 * @property string $id
 * @property string $name
 * @property string $status
 * @property bool $require_mfa
 * @property int|null $owner_id
 * @property string|null $owner_name
 * @property string|null $owner_email
 * @property int $members_count
 * @property int $workspaces_count
 * @property string|null $suspension_reason
 * @property CarbonImmutable|null $suspended_at
 * @property CarbonImmutable|null $archived_at
 * @property CarbonImmutable|null $created_at
 */
class PlatformTenant extends Model
{
    use HasUlids;

    public const string STATUS_ACTIVE = 'active';

    public const string STATUS_SUSPENDED = 'suspended';

    public const string STATUS_ARCHIVED = 'archived';

    protected $table = 'platform_tenants';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'require_mfa' => 'boolean',
            'owner_id' => 'integer',
            'members_count' => 'integer',
            'workspaces_count' => 'integer',
            'suspended_at' => 'datetime',
            'archived_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<PlatformTenantMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(PlatformTenantMember::class, 'tenant_id');
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }
}
