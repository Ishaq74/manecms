<?php

namespace Tests\Feature\Authorization\Fixtures;

use App\Domain\Authorization\Enums\Capability;
use App\Domain\Authorization\Enums\SystemRole;
use App\Domain\Authorization\Permission;

/**
 * Permissions that exercise the steps of the Policy Engine no real permission uses yet.
 */
enum TestPermission: string implements Permission
{
    case Publish = 'testing.post.publish';
    case Refund = 'testing.order.refund';
    case Deploy = 'testing.app.deploy';
    case Read = 'testing.post.read';

    public function key(): string
    {
        return $this->value;
    }

    public function capability(): Capability
    {
        return match ($this) {
            self::Deploy => Capability::CodeOnly,
            self::Read => Capability::Safe,
            default => Capability::Guarded,
        };
    }

    public function systemRoles(): array
    {
        return [SystemRole::Owner, SystemRole::Admin];
    }

    public function segregatesDuties(): bool
    {
        return $this === self::Publish;
    }

    public function requiresApproval(): bool
    {
        return $this === self::Refund;
    }

    public function label(): string
    {
        return $this->value;
    }
}
