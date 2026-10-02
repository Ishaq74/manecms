<?php

use App\Domain\Audit\AuditLog;
use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Platform\Observability\RequestContext;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Context;

final class RecordCorrelatedEvent implements ShouldQueue
{
    use Queueable;

    /** @var array{correlation: string|null, causation: string|null, request: string|null, source: string|null} */
    public static array $seen = ['correlation' => null, 'causation' => null, 'request' => null, 'source' => null];

    public function __construct(public int $userId) {}

    public function handle(AuditLog $audit): void
    {
        self::$seen = [
            'correlation' => Context::get(RequestContext::CORRELATION_ID),
            'causation' => Context::get(RequestContext::CAUSATION_ID),
            'request' => Context::get(RequestContext::REQUEST_ID),
            'source' => Context::get(RequestContext::SOURCE),
        ];

        $audit->record('identity.job.ran', actorId: $this->userId, platform: true);
    }
}

it('returns the correlation id it received', function (): void {
    $this->get(route('home'), ['X-Correlation-ID' => 'support-ticket-4711'])
        ->assertOk()
        ->assertHeader('X-Correlation-ID', 'support-ticket-4711');
});

it('replaces a malformed correlation id with a generated one', function (): void {
    $response = $this->get(route('home'), ['X-Correlation-ID' => 'bad id; drop table'])->assertOk();

    expect($response->headers->get('X-Correlation-ID'))->not->toBe('bad id; drop table')
        ->toMatch('/^[0-9A-Z]{26}$/');
});

it('carries the correlation of a request into its audit events', function (): void {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'], ['X-Correlation-ID' => 'login-trace-0001'])
        ->assertRedirect();

    $event = asOwner(fn () => AuditEvent::query()->where('action', 'identity.login.succeeded')->sole());

    expect($event->correlation_id)->toBe('login-trace-0001')
        ->and($event->source)->toBe('http');
});

it('hands the correlation of the dispatcher to its jobs', function (): void {
    $user = User::factory()->create();
    RequestContext::begin('http', 'dispatch-trace-0001');
    $dispatcherRequest = Context::get(RequestContext::REQUEST_ID);

    dispatch(new RecordCorrelatedEvent($user->id));

    $event = asOwner(fn () => AuditEvent::query()->where('action', 'identity.job.ran')->sole());

    expect(RecordCorrelatedEvent::$seen['correlation'])->toBe('dispatch-trace-0001')
        ->and(RecordCorrelatedEvent::$seen['causation'])->toBe($dispatcherRequest)
        ->and(RecordCorrelatedEvent::$seen['request'])->not->toBe($dispatcherRequest)
        ->and(RecordCorrelatedEvent::$seen['source'])->toBe('queue')
        ->and($event->correlation_id)->toBe('dispatch-trace-0001')
        ->and($event->causation_id)->toBe($dispatcherRequest);
});
