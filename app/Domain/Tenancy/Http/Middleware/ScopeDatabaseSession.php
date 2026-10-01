<?php

namespace App\Domain\Tenancy\Http\Middleware;

use App\Domain\Tenancy\Database\TenantDatabaseContext;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Scopes the database session to the signed-in user for the whole request.
 *
 * Whatever a later middleware installs, both variables are cleared before the
 * connection serves anything else.
 */
final readonly class ScopeDatabaseSession
{
    public function __construct(private TenantDatabaseContext $database) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            $this->database->apply(null, $user->id);
        }

        try {
            return $next($request);
        } finally {
            $this->database->clear();
        }
    }
}
