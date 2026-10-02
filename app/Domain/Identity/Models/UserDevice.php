<?php

namespace App\Domain\Identity\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A browser and network a user has signed in from, to spot new sign-ins.
 *
 * @property int $id
 * @property int $user_id
 * @property string $fingerprint
 * @property string|null $user_agent
 * @property string|null $ip_address
 * @property CarbonImmutable $first_seen_at
 * @property CarbonImmutable $last_seen_at
 * @property-read User $user
 */
#[Fillable(['fingerprint', 'user_agent', 'ip_address', 'first_seen_at', 'last_seen_at'])]
class UserDevice extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The agent plus the /24 (IPv4) or /48 (IPv6) network: a new address on the
     * same network is the same device, a new browser is not.
     */
    public static function fingerprint(?string $userAgent, ?string $ip): string
    {
        $network = '';

        if (is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $network = implode('.', array_slice(explode('.', $ip), 0, 3));
        } elseif (is_string($ip) && ($packed = inet_pton($ip)) !== false) {
            $network = bin2hex(substr($packed, 0, 6));
        }

        return hash('sha256', ($userAgent ?? '').'|'.$network);
    }
}
