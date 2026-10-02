<?php

namespace App\Domain\Platform\Http\Middleware;

use App\Domain\Authorization\Errors\MfaRequired;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Operators reach the back-office only with two-factor authentication enabled (todo/todo.md §20.2).
 */
final class EnsureOperatorMfa
{
    /**
     * @param  Closure(Request): Response  $next
     *
     * @throws MfaRequired
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->hasEnabledTwoFactorAuthentication()) {
            return $next($request);
        }

        if ($request->isMethod('GET') && ! $request->hasHeader('X-Livewire')) {
            return redirect()->route('security.edit', ['required' => 'mfa']);
        }

        throw new MfaRequired;
    }
}
