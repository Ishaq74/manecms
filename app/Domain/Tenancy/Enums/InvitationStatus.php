<?php

namespace App\Domain\Tenancy\Enums;

enum InvitationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Revoked = 'revoked';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Accepted => __('Accepted'),
            self::Revoked => __('Revoked'),
            self::Expired => __('Expired'),
        };
    }
}
