<?php

namespace App\Domain\Platform\Models;

use App\Domain\Authorization\Enums\SystemRole;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * A membership as the operator back-office sees it: the `platform_tenant_members` view.
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string $name
 * @property string $email
 * @property CarbonImmutable|null $email_verified_at
 * @property string|null $platform_role
 * @property string $role_name
 * @property SystemRole|null $role_system_key
 * @property bool $is_owner
 * @property CarbonImmutable|null $created_at
 */
class PlatformTenantMember extends Model
{
    use HasUlids;

    protected $table = 'platform_tenant_members';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'role_system_key' => SystemRole::class,
            'is_owner' => 'boolean',
            'email_verified_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function roleLabel(): string
    {
        return $this->role_system_key?->label() ?? $this->role_name;
    }

    public function canBeImpersonated(): bool
    {
        return $this->platform_role === null && $this->email_verified_at !== null;
    }
}
