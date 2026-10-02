<?php

namespace App\Domain\Platform\Actions;

use App\Domain\Audit\AuditLog;
use App\Domain\Platform\Enums\PlatformRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Grants or revokes the platform role. Only the platform:* commands call it: they run as the
 * table owner, the only role the `users_protect_platform_role` trigger lets through.
 */
final readonly class AssignPlatformRole
{
    public function __construct(private AuditLog $audit) {}

    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(string $email, ?PlatformRole $role): User
    {
        $user = User::query()->whereRaw('lower(email) = ?', [Str::lower(trim($email))])->first()
            ?? throw new InvalidArgumentException("No account uses the email [{$email}].");

        if ($user->platform_role === $role) {
            return $user;
        }

        DB::transaction(function () use ($user, $role): void {
            $before = $user->platform_role?->value;
            $user->platform_role = $role;
            $user->save();

            $this->audit->record(
                $role === null ? 'platform.operator.revoked' : 'platform.operator.granted',
                $user,
                ['platform_role' => $before],
                ['platform_role' => $role?->value],
                platform: true,
            );
        });

        return $user;
    }
}
