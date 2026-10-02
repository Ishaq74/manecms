<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Audit\AuditLog;
use App\Domain\Identity\DeviceName;
use App\Domain\Identity\Models\UserDevice;
use App\Domain\Identity\Notifications\NewSignInNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Remembers the device of a sign-in and warns the account owner the first time
 * an unknown device signs in, unless it is the very first device of the account.
 */
final readonly class RecordSignInDevice
{
    public function __construct(
        private Request $request,
        private AuditLog $audit,
    ) {}

    public function __invoke(User $user): void
    {
        $userAgent = mb_substr((string) $this->request->userAgent(), 0, 255);
        $ip = $this->request->ip();
        $fingerprint = UserDevice::fingerprint($userAgent, $ip);
        $now = now();

        $isNew = DB::transaction(function () use ($user, $fingerprint, $userAgent, $ip, $now): bool {
            $device = UserDevice::query()->where('user_id', $user->id)->where('fingerprint', $fingerprint)->lockForUpdate()->first();

            if ($device !== null) {
                $device->update(['last_seen_at' => $now, 'ip_address' => $ip]);

                return false;
            }

            $hadDevices = UserDevice::query()->where('user_id', $user->id)->exists();
            $device = new UserDevice(['fingerprint' => $fingerprint, 'user_agent' => $userAgent, 'ip_address' => $ip, 'first_seen_at' => $now, 'last_seen_at' => $now]);
            $device->user_id = $user->id;
            $device->save();

            $this->audit->record('identity.device.recorded', $user, after: ['device' => DeviceName::from($userAgent)], actorId: $user->id, platform: true);

            return $hadDevices;
        });

        if ($isNew) {
            $user->notify(new NewSignInNotification(DeviceName::from($userAgent), $ip, $now->toImmutable()));
        }
    }
}
