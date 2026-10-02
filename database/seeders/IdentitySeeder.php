<?php

namespace Database\Seeders;

use App\Domain\Identity\Models\UserDevice;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Known devices of the local account, so the sessions page lists some history.
 *
 * Idempotent: devices are keyed by their fingerprint.
 */
class IdentitySeeder extends Seeder
{
    private const array DEVICES = [
        ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36', '192.0.2.24', 40],
        ['Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1', '198.51.100.7', 12],
        ['Mozilla/5.0 (Macintosh; Intel Mac OS X 14_6) Gecko/20100101 Firefox/131.0', '203.0.113.80', 3],
    ];

    public function run(User $user): void
    {
        foreach (self::DEVICES as [$userAgent, $ip, $daysAgo]) {
            $device = UserDevice::query()->firstOrNew([
                'user_id' => $user->id,
                'fingerprint' => UserDevice::fingerprint($userAgent, $ip),
            ]);

            $device->fill([
                'user_agent' => $userAgent,
                'ip_address' => $ip,
                'first_seen_at' => $device->first_seen_at ?? now()->subDays($daysAgo),
                'last_seen_at' => now()->subDays(intdiv($daysAgo, 3)),
            ]);
            $device->user_id = $user->id;
            $device->save();
        }
    }
}
