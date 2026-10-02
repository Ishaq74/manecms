<?php

namespace App\Domain\Platform\Errors;

use RuntimeException;
use Throwable;

/**
 * A business error with a stable code (todo/todo.md §263).
 *
 * The message is written for the person using the application; the code is
 * what clients, logs and support tickets rely on.
 */
abstract class DomainError extends RuntimeException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    abstract public function errorCode(): string;

    public function status(): int
    {
        return 422;
    }

    public function retryable(): bool
    {
        return false;
    }
}
