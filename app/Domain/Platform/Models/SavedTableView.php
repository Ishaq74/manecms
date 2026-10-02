<?php

namespace App\Domain\Platform\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A named table state (search, sort, filters, columns, density) saved by a user.
 *
 * Row level security keeps it inside its tenant and private to its author.
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string $table_key
 * @property string $name
 * @property array<string, mixed> $state
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read User $user
 */
class SavedTableView extends Model
{
    use HasUlids;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'state' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
