<?php

namespace App\Domain\Platform;

use App\Domain\Audit\AuditLog;
use App\Domain\Platform\Errors\ImpersonationReadOnly;
use App\Domain\Platform\Models\Impersonation;
use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use LogicException;

/**
 * An operator signed in as a user for support (todo/todo.md §477): explicit (reason),
 * time-limited (30 minutes at most), audited and visible (banner), and read-only.
 *
 * The session user is swapped without firing `Login`, so the user gets neither a
 * "new device" email nor a sign-in in their history.
 */
final readonly class ImpersonationSession
{
    public const string SESSION_KEY = 'platform.impersonation';

    /** @var list<int> */
    public const array DURATIONS = [5, 15, 30];

    public function __construct(
        private Request $request,
        private AuthFactory $auth,
        private AuditLog $audit,
    ) {}

    /**
     * @throws ValidationException
     */
    public function start(User $operator, int $userId, string $reason, int $minutes): Impersonation
    {
        abort_unless($operator->isPlatformOperator() && ! $this->isActive(), 404);

        $reason = Str::squish($reason);

        Validator::make(['reason' => $reason, 'minutes' => $minutes], [
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'minutes' => ['required', 'integer', Rule::in(self::DURATIONS)],
        ])->validate();

        $target = User::query()->find($userId);

        if ($target === null || $target->is($operator) || $target->isPlatformOperator() || $target->email_verified_at === null) {
            throw ValidationException::withMessages(['user' => __('This account cannot be impersonated.')]);
        }

        $impersonation = DB::transaction(function () use ($operator, $target, $reason, $minutes): Impersonation {
            Impersonation::query()
                ->where('operator_id', $operator->id)
                ->whereNull('ended_at')
                ->update(['ended_at' => now(), 'end_reason' => Impersonation::ENDED_EXPIRED]);

            $impersonation = (new Impersonation)->forceFill([
                'operator_id' => $operator->id,
                'user_id' => $target->id,
                'reason' => $reason,
                'started_at' => now(),
                'expires_at' => now()->addMinutes($minutes),
            ]);
            $impersonation->save();

            $this->audit->record('platform.impersonation.started', $target, after: ['reason' => $reason, 'minutes' => $minutes], actorId: $operator->id, platform: true);

            return $impersonation;
        });

        $this->switchTo($target);
        $this->request->session()->put(self::SESSION_KEY, $impersonation->id);

        return $impersonation;
    }

    public function current(): ?Impersonation
    {
        $id = $this->request->hasSession() ? $this->request->session()->get(self::SESSION_KEY) : null;

        return is_string($id) ? Impersonation::query()->find($id) : null;
    }

    public function isActive(): bool
    {
        return $this->current() !== null;
    }

    /**
     * End the impersonation and give the session back to the operator.
     */
    public function stop(string $endReason = Impersonation::ENDED_STOPPED): ?User
    {
        $impersonation = $this->current();

        if ($impersonation === null) {
            return null;
        }

        DB::transaction(function () use ($impersonation, $endReason): void {
            if ($impersonation->ended_at === null) {
                $impersonation->forceFill(['ended_at' => now(), 'end_reason' => $endReason])->save();
            }

            $this->audit->record(
                $endReason === Impersonation::ENDED_EXPIRED ? 'platform.impersonation.expired' : 'platform.impersonation.ended',
                $impersonation->user,
                after: ['impersonation_id' => $impersonation->id],
                actorId: $impersonation->operator_id,
                platform: true,
            );
        });

        $this->request->session()->forget(self::SESSION_KEY);
        $this->switchTo($impersonation->operator);

        return $impersonation->operator;
    }

    /**
     * Refuse, and audit, an attempt to change something while impersonating.
     *
     * @throws ImpersonationReadOnly
     */
    public function refuse(string $attempt): never
    {
        $impersonation = $this->current();

        $this->audit->record(
            'platform.impersonation.blocked',
            $impersonation?->user,
            after: ['attempt' => mb_substr($attempt, 0, 200)],
            actorId: $impersonation?->operator_id,
            platform: true,
        );

        throw new ImpersonationReadOnly;
    }

    private function switchTo(User $user): void
    {
        $guard = $this->auth->guard('web');

        if (! $guard instanceof SessionGuard) {
            throw new LogicException('Impersonation needs the session guard.');
        }

        $session = $this->request->session();
        $session->migrate(true);
        $session->put($guard->getName(), $user->getAuthIdentifier());
        $guard->setUser($user);
    }
}
