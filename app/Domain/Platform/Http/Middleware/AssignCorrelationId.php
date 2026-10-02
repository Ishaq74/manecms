<?php

namespace App\Domain\Platform\Http\Middleware;

use App\Domain\Platform\Observability\RequestContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gives every request a correlation id, accepted from the caller when well formed.
 */
final class AssignCorrelationId
{
    public const string HEADER = 'X-Correlation-ID';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        RequestContext::begin('http', $request->headers->get(self::HEADER));

        $response = $next($request);
        $response->headers->set(self::HEADER, RequestContext::correlationId());

        return $response;
    }
}
