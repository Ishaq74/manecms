<?php

namespace App\Domain\Tenancy\Http\Middleware;

use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Models\Workspace;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Installs the tenant context from the {workspace} route parameter.
 *
 * Unknown, archived and foreign workspaces, archived tenants and workspaces
 * the member is restricted from all answer 404, so a user outside them cannot
 * tell whether a workspace exists.
 */
final readonly class ResolveWorkspace
{
    public function __construct(private TenantContext $context) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $workspaceId = $request->route('workspace');
        $user = $request->user();

        if (! is_string($workspaceId) || ! Str::isUlid($workspaceId) || ! $user instanceof User) {
            abort(404);
        }

        $workspace = Workspace::query()->active()->find($workspaceId);
        $member = $workspace === null
            ? null
            : $user->tenantMemberships()->with('tenant')->where('tenant_id', $workspace->tenant_id)->first();

        // A member restricted to other workspaces cannot tell this one exists either.
        if ($workspace === null || $member === null || $member->tenant->isArchived() || ! $member->allowsWorkspace($workspace->id)) {
            abort(404);
        }

        $this->context->install($member, $workspace);

        if ($member->last_workspace_id !== $workspace->id) {
            $member->update(['last_workspace_id' => $workspace->id]);
        }

        return $next($request);
    }
}
