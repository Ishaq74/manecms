<?php

namespace App\Domain\Authorization\Enums;

enum DecisionType: string
{
    case Allow = 'allow';
    case AllowWithConstraints = 'allow_with_constraints';
    case Deny = 'deny';
    case RequireApproval = 'require_approval';
}
