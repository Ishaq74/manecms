<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Audit\AuditLog;
use App\Domain\Authorization\Errors\AuthorizationDenied;
use App\Domain\Authorization\PolicyEngine;
use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Permissions\TenancyPermission;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Limits a member to some workspaces; an empty list gives back every workspace.
 */
final readonly class RestrictMemberWorkspaces
{
    public function __construct(
        private TenantContext $context,
        private PolicyEngine $engine,
        private AuditLog $audit,
    ) {}

    /**
     * @param  list<string>  $workspaceIds
     *
     * @throws AuthorizationDenied
     * @throws ModelNotFoundException<TenantMember>
     * @throws ValidationException
     */
    public function __invoke(User $actor, string $memberId, array $workspaceIds): TenantMember
    {
        $tenant = $this->context->tenant();
        $member = $tenant->members()->with('role')->findOrFail($memberId);

        $this->engine->authorize($actor, TenancyPermission::MemberRestrict, targetMember: $member);

        $workspaceIds = array_values(array_unique($workspaceIds));

        if ($tenant->workspaces()->active()->whereIn('id', $workspaceIds)->count() !== count($workspaceIds)) {
            throw ValidationException::withMessages(['workspaces' => __('Choose workspaces of this space.')]);
        }

        $before = $member->restrictedWorkspaces()->pluck('workspaces.id')->sort()->values()->all();
        sort($workspaceIds);

        if ($before === $workspaceIds) {
            return $member;
        }

        DB::transaction(function () use ($member, $workspaceIds, $before, $actor): void {
            $member->restrictedWorkspaces()->syncWithPivotValues($workspaceIds, ['tenant_id' => $member->tenant_id]);

            if ($member->last_workspace_id !== null && $workspaceIds !== [] && ! in_array($member->last_workspace_id, $workspaceIds, true)) {
                $member->update(['last_workspace_id' => null]);
            }

            $this->audit->record('tenancy.member.restricted', $member, ['workspaces' => $before], ['workspaces' => $workspaceIds], actorId: $actor->id);
        });

        return $member;
    }
}
