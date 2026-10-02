<?php

namespace App\Domain\Authorization\Errors;

use App\Domain\Platform\Errors\DomainError;

/**
 * The action is allowed only once approved; approvals arrive with P15.
 */
final class ApprovalRequired extends DomainError
{
    public const string CODE = 'APPROVAL_REQUIRED';

    public function __construct(public readonly string $permission)
    {
        parent::__construct(__('This action must be approved before it runs.'));
    }

    public function errorCode(): string
    {
        return self::CODE;
    }

    public function status(): int
    {
        return 409;
    }
}
