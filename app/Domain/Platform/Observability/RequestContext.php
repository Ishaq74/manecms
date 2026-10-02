<?php

namespace App\Domain\Platform\Observability;

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;

/**
 * Correlation data shared by a request, its jobs, its logs and its audit events.
 *
 * Stored in Laravel's Context, which travels with queued jobs and is attached
 * to every log record.
 */
final class RequestContext
{
    public const string CORRELATION_ID = 'correlation_id';

    public const string CAUSATION_ID = 'causation_id';

    public const string REQUEST_ID = 'request_id';

    public const string SOURCE = 'source';

    public const string TENANT_ID = 'tenant_id';

    public const string USER_ID = 'user_id';

    public static function isValidCorrelationId(?string $value): bool
    {
        return $value !== null && preg_match('/^[A-Za-z0-9-]{8,64}$/', $value) === 1;
    }

    public static function begin(string $source, ?string $correlationId = null): void
    {
        Context::add(self::CORRELATION_ID, self::isValidCorrelationId($correlationId) ? $correlationId : (string) Str::ulid());
        Context::add(self::REQUEST_ID, (string) Str::ulid());
        Context::add(self::SOURCE, $source);
    }

    public static function correlationId(): string
    {
        $correlationId = Context::get(self::CORRELATION_ID);

        if (! is_string($correlationId)) {
            self::begin(app()->runningInConsole() ? 'cli' : 'http');

            return self::correlationId();
        }

        return $correlationId;
    }

    public static function causationId(): ?string
    {
        $causationId = Context::get(self::CAUSATION_ID);

        return is_string($causationId) ? $causationId : null;
    }

    public static function source(): string
    {
        $source = Context::get(self::SOURCE);

        return is_string($source) ? $source : (app()->runningInConsole() ? 'cli' : 'http');
    }
}
