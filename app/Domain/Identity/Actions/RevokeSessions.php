<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Audit\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Signs a user out of other browsers (sessions stored by the `database` driver).
 */
final readonly class RevokeSessions
{
    public function __construct(
        private ConfirmIdentity $confirmIdentity,
        private AuditLog $audit,
    ) {}

    /**
     * The public handle of a session: its id is a bearer secret and never leaves the server.
     */
    public static function keyOf(string $sessionId): string
    {
        return hash('sha256', $sessionId);
    }

    /**
     * Revoke one other session, found by its key. The current one is signed out with "Log out" instead.
     *
     * @throws ValidationException
     */
    public function one(User $user, string $sessionKey, string $currentSessionId): void
    {
        if (hash_equals(self::keyOf($currentSessionId), $sessionKey)) {
            throw ValidationException::withMessages(['session' => __('Use "Log out" to end the current session.')]);
        }

        $sessionId = DB::table('sessions')->where('user_id', $user->id)->pluck('id')
            ->first(fn (mixed $id): bool => is_string($id) && hash_equals(self::keyOf($id), $sessionKey));

        if (! is_string($sessionId)) {
            throw ValidationException::withMessages(['session' => __('This session has already ended.')]);
        }

        $deleted = DB::transaction(function () use ($user, $sessionId): int {
            $deleted = DB::table('sessions')->where('user_id', $user->id)->where('id', $sessionId)->delete();

            if ($deleted > 0) {
                $this->audit->record('identity.session.revoked', $user, after: ['sessions' => 1], actorId: $user->id, platform: true);
            }

            return $deleted;
        });

        if ($deleted === 0) {
            throw ValidationException::withMessages(['session' => __('This session has already ended.')]);
        }
    }

    /**
     * Revoke every other session and rotate the "remember me" token, so no other browser stays signed in.
     *
     * @throws ValidationException
     */
    public function others(User $user, #[\SensitiveParameter] string $password, string $currentSessionId): int
    {
        ($this->confirmIdentity)($user, $password);

        return DB::transaction(function () use ($user, $currentSessionId): int {
            $deleted = DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', $currentSessionId)->delete();

            $user->setRememberToken(Str::random(60));
            $user->save();

            $this->audit->record('identity.session.revoked_others', $user, after: ['sessions' => $deleted], actorId: $user->id, platform: true);

            return $deleted;
        });
    }
}
