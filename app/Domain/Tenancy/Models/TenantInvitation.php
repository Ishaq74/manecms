<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Authorization\Models\Role;
use App\Domain\Tenancy\Enums\InvitationStatus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\TenantInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An invitation to join a tenant. Only the SHA-256 hash of its token is stored.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $email
 * @property string $role_id
 * @property list<string>|null $workspace_ids
 * @property string $token_hash
 * @property int|null $invited_by
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $accepted_at
 * @property int|null $accepted_by
 * @property CarbonImmutable|null $revoked_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Tenant $tenant
 * @property-read Role $role
 * @property-read User|null $inviter
 */
#[Fillable(['email', 'role_id', 'workspace_ids', 'token_hash', 'invited_by', 'expires_at'])]
#[Hidden(['token_hash'])]
#[UseFactory(TenantInvitationFactory::class)]
class TenantInvitation extends Model
{
    /** @use HasFactory<TenantInvitationFactory> */
    use HasFactory, HasUlids;

    public const int VALID_DAYS = 7;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'workspace_ids' => 'array',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function status(): InvitationStatus
    {
        return match (true) {
            $this->accepted_at !== null => InvitationStatus::Accepted,
            $this->revoked_at !== null => InvitationStatus::Revoked,
            $this->expires_at->isPast() => InvitationStatus::Expired,
            default => InvitationStatus::Pending,
        };
    }

    /**
     * Not yet accepted nor revoked, whether expired or not: the rows the unique index covers.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereNull('accepted_at')->whereNull('revoked_at');
    }
}
