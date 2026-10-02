<?php

namespace App\Domain\Platform\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The back-office does not exist for anyone but platform operators: everyone else, guests
 * included, gets 404.
 */
final class EnsurePlatformOperator
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->isPlatformOperator(), 404);

        return $next($request);
    }
}
