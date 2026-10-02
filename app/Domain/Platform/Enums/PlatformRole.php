<?php

namespace App\Domain\Platform\Enums;

/**
 * Roles of the people who run the platform, distinct from tenant roles (todo/todo.md §20.2).
 */
enum PlatformRole: string
{
    case Operator = 'operator';
}
