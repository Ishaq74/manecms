<?php

namespace App\Domain\Tenancy\Context;

use App\Domain\Tenancy\Database\TenantDatabaseContext;
use App\Domain\Tenancy\Enums\TenantRole;
use App\Domain\Tenancy\Exceptions\TenantContextRequired;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Models\Workspace;
use LogicException;

/**
 * The tenant, workspace and membership of the current request.
 *
 * Bound as a scoped instance. Reading it before it is installed throws, so code
 * that forgets to resolve a workspace fails closed instead of guessing a tenant.
 */
final class TenantContext
{
    private ?TenantMember $member = null;

    private ?Workspace $workspace = null;

    public function __construct(private readonly TenantDatabaseContext $database) {}

    public function install(TenantMember $member, Workspace $workspace): void
    {
        if ($member->tenant_id !== $workspace->tenant_id) {
            throw new LogicException('The membership and the workspace belong to different tenants.');
        }

        $this->database->apply($member->tenant_id, $member->user_id);

        $this->member = $member;
        $this->workspace = $workspace;
    }

    public function isInstalled(): bool
    {
        return $this->member !== null && $this->workspace !== null;
    }

    public function member(): TenantMember
    {
        return $this->member ?? throw new TenantContextRequired;
    }

    public function workspace(): Workspace
    {
        return $this->workspace ?? throw new TenantContextRequired;
    }

    public function tenant(): Tenant
    {
        return $this->member()->tenant;
    }

    public function role(): TenantRole
    {
        return $this->member()->role;
    }
}
