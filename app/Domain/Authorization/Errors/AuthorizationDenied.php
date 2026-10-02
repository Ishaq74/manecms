<?php

namespace App\Domain\Authorization\Errors;

use App\Domain\Authorization\Enums\DenialReason;
use App\Domain\Platform\Errors\DomainError;

final class AuthorizationDenied extends DomainError
{
    public const string CODE = 'AUTHORIZATION_DENIED';

    public function __construct(public readonly DenialReason $reason)
    {
        parent::__construct(match ($reason) {
            DenialReason::MfaRequired => __('This space requires two-factor authentication for this action.'),
            DenialReason::TenantArchived => __('This space is archived.'),
            default => __('You are not allowed to perform this action.'),
        });
    }

    public function errorCode(): string
    {
        return self::CODE;
    }

    public function status(): int
    {
        return 403;
    }
}
