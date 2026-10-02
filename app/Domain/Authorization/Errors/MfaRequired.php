<?php

namespace App\Domain\Authorization\Errors;

use App\Domain\Platform\Errors\DomainError;

final class MfaRequired extends DomainError
{
    public const string CODE = 'MFA_REQUIRED';

    public function __construct()
    {
        parent::__construct(__('This space requires two-factor authentication. Enable it in your security settings.'));
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
