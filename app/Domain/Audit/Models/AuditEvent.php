<?php

namespace App\Domain\Audit\Models;

use App\Domain\Audit\Policies\AuditEventPolicy;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\AuditEventFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An append-only record of a sensitive operation (todo/todo.md §153).
 *
 * @property string $id
 * @property string|null $tenant_id
 * @property int|null $actor_id
 * @property string $action
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property array<array-key, mixed>|null $before
 * @property array<array-key, mixed>|null $after
 * @property string|null $reason
 * @property string $source
 * @property string|null $ip
 * @property string|null $user_agent
 * @property string $correlation_id
 * @property string|null $causation_id
 * @property CarbonImmutable $occurred_at
 * @property-read User|null $actor
 */
#[UseFactory(AuditEventFactory::class)]
#[UsePolicy(AuditEventPolicy::class)]
class AuditEvent extends Model
{
    /** @use HasFactory<AuditEventFactory> */
    use HasFactory, HasUlids;

    public $timestamps = false;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actor_id' => 'integer',
            'before' => 'array',
            'after' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
