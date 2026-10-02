<?php

namespace App\Domain\Authorization\Http\Middleware;

use App\Domain\Authorization\Errors\MfaRequired;
use App\Domain\Authorization\PolicyEngine;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends the privileged members of a space that requires 2FA to their security
 * settings until they enable it. Runs after ResolveWorkspace.
 */
final readonly class EnsureTenantMfa
{
    public function __construct(private PolicyEngine $engine) {}

    /**
     * @param  Closure(Request): Response  $next
     *
     * @throws MfaRequired
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $this->engine->requiresMfaEnrolment($user)) {
            return $next($request);
        }

        if ($request->isMethod('GET') && ! $request->hasHeader('X-Livewire')) {
            // A query string survives the password confirmation that guards the security page; a flash would not.
            return redirect()->route('security.edit', ['required' => 'mfa']);
        }

        throw new MfaRequired;
    }
}
