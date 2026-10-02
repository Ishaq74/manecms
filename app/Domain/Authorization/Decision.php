<?php

namespace App\Domain\Authorization;

use App\Domain\Authorization\Enums\DecisionType;
use App\Domain\Authorization\Enums\DenialReason;

/**
 * The typed answer of the Policy Engine (todo/todo.md §28.1).
 */
final readonly class Decision
{
    /**
     * @param  list<string>  $constraints
     */
    private function __construct(
        public DecisionType $type,
        public ?DenialReason $reason = null,
        public array $constraints = [],
    ) {}

    public static function allow(): self
    {
        return new self(DecisionType::Allow);
    }

    /**
     * @param  list<string>  $constraints
     */
    public static function allowWithConstraints(array $constraints): self
    {
        return new self(DecisionType::AllowWithConstraints, constraints: $constraints);
    }

    public static function deny(DenialReason $reason): self
    {
        return new self(DecisionType::Deny, $reason);
    }

    public static function requireApproval(): self
    {
        return new self(DecisionType::RequireApproval);
    }

    public function allows(): bool
    {
        return $this->type === DecisionType::Allow || $this->type === DecisionType::AllowWithConstraints;
    }
}
