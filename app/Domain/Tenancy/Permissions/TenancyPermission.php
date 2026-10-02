<?php

namespace App\Domain\Tenancy\Permissions;

use App\Domain\Authorization\Enums\Capability;
use App\Domain\Authorization\Enums\SystemRole;
use App\Domain\Authorization\Permission;

enum TenancyPermission: string implements Permission
{
    case MemberView = 'tenancy.member.view';
    case MemberInvite = 'tenancy.member.invite';
    case MemberUpdateRole = 'tenancy.member.update-role';
    case MemberRestrict = 'tenancy.member.restrict';
    case MemberRemove = 'tenancy.member.remove';
    case RoleManage = 'tenancy.role.manage';
    case WorkspaceCreate = 'tenancy.workspace.create';
    case WorkspaceUpdate = 'tenancy.workspace.update';
    case WorkspaceArchive = 'tenancy.workspace.archive';
    case TenantUpdate = 'tenancy.tenant.update';
    case TenantSecurity = 'tenancy.tenant.security';
    case TenantTransfer = 'tenancy.tenant.transfer';
    case TenantArchive = 'tenancy.tenant.archive';

    public function key(): string
    {
        return $this->value;
    }

    public function capability(): Capability
    {
        return match ($this) {
            self::MemberView => Capability::Safe,
            self::RoleManage, self::TenantSecurity, self::TenantTransfer, self::TenantArchive => Capability::Privileged,
            default => Capability::Guarded,
        };
    }

    public function systemRoles(): array
    {
        return match ($this) {
            self::MemberView => [SystemRole::Owner, SystemRole::Admin, SystemRole::Member],
            self::TenantSecurity, self::TenantTransfer, self::TenantArchive => [SystemRole::Owner],
            default => [SystemRole::Owner, SystemRole::Admin],
        };
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
        return match ($this) {
            self::MemberView => __('View members'),
            self::MemberInvite => __('Invite members'),
            self::MemberUpdateRole => __('Change member roles'),
            self::MemberRestrict => __('Restrict members to workspaces'),
            self::MemberRemove => __('Remove members'),
            self::RoleManage => __('Manage roles'),
            self::WorkspaceCreate => __('Create workspaces'),
            self::WorkspaceUpdate => __('Rename workspaces'),
            self::WorkspaceArchive => __('Archive workspaces'),
            self::TenantUpdate => __('Rename the space'),
            self::TenantSecurity => __('Manage space security'),
            self::TenantTransfer => __('Transfer ownership'),
            self::TenantArchive => __('Archive the space'),
        };
    }
}
