<?php

namespace App\Domain\Tenancy\Exceptions;

use App\Domain\Platform\Errors\DomainError;

final class TenantContextRequired extends DomainError
{
    public const string CODE = 'TENANT_CONTEXT_REQUIRED';

    public function __construct()
    {
        parent::__construct(__('No workspace is selected for this action.'));
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
