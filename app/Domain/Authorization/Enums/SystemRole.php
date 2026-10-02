<?php

namespace App\Domain\Authorization\Enums;

/**
 * The roles every tenant starts with (todo/todo.md §20.1).
 *
 * They are stored as rows of `roles` with a `system_key`, so a member always
 * points at a role row; this enum only names them in code.
 */
enum SystemRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Owner => __('Owner'),
            self::Admin => __('Admin'),
            self::Member => __('Member'),
        };
    }
}
