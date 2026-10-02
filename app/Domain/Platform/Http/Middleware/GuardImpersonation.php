<?php

namespace App\Domain\Platform\Http\Middleware;

use App\Domain\Platform\Errors\ImpersonationReadOnly;
use App\Domain\Platform\ImpersonationSession;
use App\Domain\Platform\Models\Impersonation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends an expired impersonation and keeps an active one read-only.
 *
 * Livewire updates pass here and are checked method by method (PlatformServiceProvider);
 * every other write is refused, except signing out and ending the impersonation.
 */
final readonly class GuardImpersonation
{
    private const array ALLOWED_WRITES = ['logout', 'impersonation.stop', '*livewire.update'];

    public function __construct(private ImpersonationSession $impersonation) {}

    /**
     * @param  Closure(Request): Response  $next
     *
     * @throws ImpersonationReadOnly
     */
    public function handle(Request $request, Closure $next): Response
    {
        $current = $this->impersonation->current();

        if ($current === null) {
            return $next($request);
        }

        if (! $current->isOpen()) {
            $this->impersonation->stop(Impersonation::ENDED_EXPIRED);

            return $request->hasHeader('X-Livewire')
                ? response()->json(['code' => 'IMPERSONATION_EXPIRED'], 409)
                : redirect()->route('platform.dashboard')->with('status', 'impersonation-expired');
        }

        if (! $request->isMethodSafe() && ! $request->routeIs(...self::ALLOWED_WRITES)) {
            $this->impersonation->refuse($request->method().' '.$request->path());
        }

        return $next($request);
    }
}
