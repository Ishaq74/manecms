<?php

namespace App\Domain\Tenancy\Enums;

enum TenantRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function canManageWorkspaces(): bool
    {
        return $this === self::Owner || $this === self::Admin;
    }

    public function canManageTenant(): bool
    {
        return $this === self::Owner || $this === self::Admin;
    }
}
