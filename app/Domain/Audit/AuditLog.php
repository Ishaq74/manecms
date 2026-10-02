<?php

namespace App\Domain\Audit;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Platform\Observability\Redactor;
use App\Domain\Platform\Observability\RequestContext;
use App\Domain\Tenancy\Database\TenantDatabaseContext;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Writes audit events on the current connection, so they share the transaction
 * of the action they describe and disappear with it on rollback.
 */
final readonly class AuditLog
{
    public function __construct(
        private TenantDatabaseContext $database,
        private AuthFactory $auth,
        private Request $request,
    ) {}

    /**
     * @param  array<array-key, mixed>  $before
     * @param  array<array-key, mixed>  $after
     */
    public function record(
        string $action,
        ?Model $subject = null,
        array $before = [],
        array $after = [],
        ?string $reason = null,
        ?int $actorId = null,
        bool $platform = false,
    ): AuditEvent {
        $source = RequestContext::source();
        $actor = $actorId ?? $this->auth->guard()->id();
        $subjectId = $subject?->getKey();

        return AuditEvent::query()->create([
            'tenant_id' => $platform ? null : $this->database->current()['tenant'],
            'actor_id' => is_int($actor) ? $actor : null,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => is_scalar($subjectId) ? (string) $subjectId : null,
            'before' => $before === [] ? null : Redactor::redact($before),
            'after' => $after === [] ? null : Redactor::redact($after),
            'reason' => $reason,
            'source' => $source,
            'ip' => $source === 'http' ? $this->request->ip() : null,
            'user_agent' => $source === 'http' ? mb_substr((string) $this->request->userAgent(), 0, 255) : null,
            'correlation_id' => RequestContext::correlationId(),
            'causation_id' => RequestContext::causationId(),
            'occurred_at' => now(),
        ]);
    }
}
