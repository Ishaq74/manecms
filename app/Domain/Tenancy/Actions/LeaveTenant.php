<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Audit\AuditLog;
use App\Domain\Tenancy\Context\TenantContext;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A member leaves the current tenant. The owner must transfer ownership first.
 */
final readonly class LeaveTenant
{
    public function __construct(
        private TenantContext $context,
        private AuditLog $audit,
    ) {}

    /**
     * @throws ValidationException
     */
    public function __invoke(User $user): void
    {
        $member = $this->context->member();

        if ($member->user_id !== $user->id) {
            throw ValidationException::withMessages(['leave' => __('You can only leave a space for yourself.')]);
        }

        if ($member->is_owner) {
            throw ValidationException::withMessages(['leave' => __('Transfer the ownership of this space before leaving it.')]);
        }

        DB::transaction(function () use ($member, $user): void {
            $this->audit->record('tenancy.member.left', $member, ['user_id' => $user->id], actorId: $user->id);
            $member->delete();
        });
    }
}
