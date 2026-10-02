<?php

namespace App\Domain\Platform\Observability;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Masks secrets in the context and extra data of every log record.
 */
final class RedactLogRecord implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            context: Redactor::redact($record->context),
            extra: Redactor::redact($record->extra),
        );
    }
}
