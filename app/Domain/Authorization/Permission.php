<?php

namespace App\Domain\Authorization;

use App\Domain\Authorization\Enums\Capability;
use App\Domain\Authorization\Enums\SystemRole;

/**
 * A permission declared in code by the context that owns it (todo/todo.md §28).
 *
 * Implemented by backed enums listed in config/authorization.php; the
 * `permissions` table is a synchronised copy, never the source of truth.
 */
interface Permission
{
    /**
     * `<context>.<resource>.<action>`, e.g. `tenancy.member.invite`.
     */
    public function key(): string;

    public function capability(): Capability;

    /**
     * The system roles that receive the permission in every tenant.
     *
     * @return list<SystemRole>
     */
    public function systemRoles(): array;

    /**
     * Whether the person who created a resource is barred from this action on it.
     */
    public function segregatesDuties(): bool;

    public function requiresApproval(): bool;

    public function label(): string;
}
