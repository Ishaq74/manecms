<?php

namespace App\Domain\Platform\Health;

/**
 * One line of the operator health page (todo/todo.md §475).
 */
final readonly class HealthCheck
{
    public const string OK = 'ok';

    public const string WARNING = 'warning';

    public const string CRITICAL = 'critical';

    public function __construct(
        public string $key,
        public string $label,
        public string $status,
        public string $detail,
    ) {}
}
