<?php

namespace App\Domain\Tenancy\Exceptions;

use RuntimeException;

final class TenantContextRequired extends RuntimeException
{
    public const string CODE = 'TENANT_CONTEXT_REQUIRED';

    public function __construct()
    {
        parent::__construct('No tenant context is installed for this request.');
    }
}
