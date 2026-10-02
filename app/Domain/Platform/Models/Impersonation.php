<?php

namespace App\Domain\Platform\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\ImpersonationFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An operator signed in as a user for support (todo/todo.md §477).
 *
 * @property string $id
 * @property int $operator_id
 * @property int $user_id
 * @property string $reason
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $ended_at
 * @property string|null $end_reason
 * @property-read User $operator
 * @property-read User $user
 */
#[UseFactory(ImpersonationFactory::class)]
class Impersonation extends Model
{
    /** @use HasFactory<ImpersonationFactory> */
    use HasFactory, HasUlids;

    public const int MAX_MINUTES = 30;

    public const string ENDED_STOPPED = 'stopped';

    public const string ENDED_EXPIRED = 'expired';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'operator_id' => 'integer',
            'user_id' => 'integer',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOpen(): bool
    {
        return $this->ended_at === null && $this->expires_at->isFuture();
    }
}
