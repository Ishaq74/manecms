<?php

namespace App\Domain\Authorization\Enums;

/**
 * How sensitive a permission is (todo/todo.md §30).
 */
enum Capability: string
{
    /** Everyday configuration, grantable to any role. */
    case Safe = 'safe';

    /** Grantable to any role; every use is audited. */
    case Guarded = 'guarded';

    /** Reserved to the owner and admin system roles; needs MFA when the space requires it. */
    case Privileged = 'privileged';

    /** Changed by a deployment only. */
    case CodeOnly = 'code_only';

    /** Never changed once set. */
    case Immutable = 'immutable';

    public function isGrantable(): bool
    {
        return $this !== self::CodeOnly && $this !== self::Immutable;
    }

    public function isGrantableToCustomRoles(): bool
    {
        return $this === self::Safe || $this === self::Guarded;
    }

    public function label(): string
    {
        return match ($this) {
            self::Safe => __('Standard'),
            self::Guarded => __('Audited'),
            self::Privileged => __('Privileged'),
            self::CodeOnly => __('Code only'),
            self::Immutable => __('Immutable'),
        };
    }
}
