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

final readonly class RemoveMember
{
    public function __construct(
        private TenantContext $context,
        private PolicyEngine $engine,
        private AuditLog $audit,
    ) {}

    /**
     * @throws AuthorizationDenied
     * @throws ModelNotFoundException<TenantMember>
     */
    public function __invoke(User $actor, string $memberId): void
    {
        $member = $this->context->tenant()->members()->with('role')->findOrFail($memberId);

        $this->engine->authorize($actor, TenancyPermission::MemberRemove, targetMember: $member);

        DB::transaction(function () use ($member, $actor): void {
            $this->audit->record('tenancy.member.removed', $member, ['user_id' => $member->user_id, 'role' => $member->role->key], actorId: $actor->id);
            $member->delete();
        });
    }
}
