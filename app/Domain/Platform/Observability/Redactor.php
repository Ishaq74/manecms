<?php

namespace App\Domain\Platform\Observability;

/**
 * Masks secrets before data reaches the audit trail or the logs (todo/todo.md §367, §385).
 */
final class Redactor
{
    public const string MASK = '[redacted]';

    private const string SENSITIVE_KEY = '/pass(word|phrase)?|secret|token|api[_-]?key|authorization|cookie|recovery[_-]?codes/i';

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public static function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && preg_match(self::SENSITIVE_KEY, $key) === 1) {
                $data[$key] = self::MASK;
            } elseif (is_array($value)) {
                $data[$key] = self::redact($value);
            }
        }

        return $data;
    }
}
