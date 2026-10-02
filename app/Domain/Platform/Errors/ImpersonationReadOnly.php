<?php

namespace App\Domain\Platform\Errors;

final class ImpersonationReadOnly extends DomainError
{
    public const string CODE = 'IMPERSONATION_READ_ONLY';

    public function __construct()
    {
        parent::__construct(__('You are signed in as someone else for support: nothing can be changed.'));
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
