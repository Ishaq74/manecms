<?php

namespace App\Domain\Audit\Permissions;

use App\Domain\Authorization\Enums\Capability;
use App\Domain\Authorization\Enums\SystemRole;
use App\Domain\Authorization\Permission;

enum AuditPermission: string implements Permission
{
    case EventView = 'audit.event.view';

    public function key(): string
    {
        return $this->value;
    }

    public function capability(): Capability
    {
        return Capability::Guarded;
    }

    public function systemRoles(): array
    {
        return [SystemRole::Owner, SystemRole::Admin];
    }

    public function segregatesDuties(): bool
    {
        return false;
    }

    public function requiresApproval(): bool
    {
        return false;
    }

    public function label(): string
    {
        return __('View the audit trail');
    }
}
