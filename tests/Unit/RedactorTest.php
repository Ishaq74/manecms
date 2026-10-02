<?php

use App\Domain\Platform\Observability\RedactLogRecord;
use App\Domain\Platform\Observability\Redactor;
use Monolog\Level;
use Monolog\LogRecord;

it('masks sensitive keys at any depth, whatever their case', function (): void {
    expect(Redactor::redact([
        'name' => 'Ada',
        'Password' => 'secret-1',
        'profile' => ['API_KEY' => 'secret-2', 'city' => 'Lyon'],
        'two_factor_recovery_codes' => ['a', 'b'],
        'headers' => ['Authorization' => 'Bearer x', 'Cookie' => 'session=y'],
    ]))->toBe([
        'name' => 'Ada',
        'Password' => Redactor::MASK,
        'profile' => ['API_KEY' => Redactor::MASK, 'city' => 'Lyon'],
        'two_factor_recovery_codes' => Redactor::MASK,
        'headers' => ['Authorization' => Redactor::MASK, 'Cookie' => Redactor::MASK],
    ]);
});

it('masks secrets in log records', function (): void {
    $record = new LogRecord(
        datetime: new DateTimeImmutable,
        channel: 'json',
        level: Level::Info,
        message: 'Signed in',
        context: ['user' => 7, 'password' => 'secret-1'],
        extra: ['token' => 'secret-2'],
    );

    $redacted = (new RedactLogRecord)($record);

    expect($redacted->context)->toBe(['user' => 7, 'password' => Redactor::MASK])
        ->and($redacted->extra)->toBe(['token' => Redactor::MASK]);
});
