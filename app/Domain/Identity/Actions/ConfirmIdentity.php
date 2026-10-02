<?php

namespace App\Domain\Identity\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

/**
 * Re-checks who is at the keyboard before a sensitive action (todo/todo.md §20):
 * the password, plus a two-factor code when the account has 2FA enabled.
 */
final readonly class ConfirmIdentity
{
    public function __construct(private TwoFactorAuthenticationProvider $twoFactor) {}

    /**
     * @throws ValidationException
     */
    public function __invoke(User $user, #[\SensitiveParameter] string $password, #[\SensitiveParameter] ?string $code = null): void
    {
        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages(['password' => __('The password is incorrect.')]);
        }

        if (! $user->hasEnabledTwoFactorAuthentication()) {
            return;
        }

        $secret = $user->two_factor_secret === null ? '' : decrypt($user->two_factor_secret);

        if (! is_string($secret) || $code === null || ! $this->twoFactor->verify($secret, $code)) {
            throw ValidationException::withMessages(['code' => __('The two-factor code is incorrect.')]);
        }
    }

    public function needsCode(User $user): bool
    {
        return $user->hasEnabledTwoFactorAuthentication();
    }
}
