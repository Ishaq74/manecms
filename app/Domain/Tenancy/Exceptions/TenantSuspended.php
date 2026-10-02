<?php

namespace App\Domain\Tenancy\Exceptions;

use App\Domain\Platform\Errors\DomainError;

final class TenantSuspended extends DomainError
{
    public const string CODE = 'TENANT_SUSPENDED';

    public function __construct(public readonly ?string $reason = null)
    {
        parent::__construct($reason === null
            ? __('This space is suspended. Contact ManeCMS support.')
            : __('This space is suspended by the ManeCMS team: :reason', ['reason' => $reason]));
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
