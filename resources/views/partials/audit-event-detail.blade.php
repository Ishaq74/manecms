@php
    /** @var App\Domain\Audit\Models\AuditEvent $event */
@endphp

<dl class="grid gap-3 text-xs md:grid-cols-2">
    <div>
        <dt class="font-medium">{{ __('Before') }}</dt>
        <dd><pre class="whitespace-pre-wrap">{{ json_encode($event->before, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></dd>
    </div>

    <div>
        <dt class="font-medium">{{ __('After') }}</dt>
        <dd><pre class="whitespace-pre-wrap">{{ json_encode($event->after, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></dd>
    </div>

    <div>
        <dt class="font-medium">{{ __('Correlation') }}</dt>
        <dd class="font-mono">{{ $event->correlation_id }}</dd>
    </div>

    <div>
        <dt class="font-medium">{{ __('Origin') }}</dt>
        <dd>{{ $event->ip ?? '-' }} · {{ $event->user_agent ?? '-' }}</dd>
    </div>

    @if ($event->reason)
        <div>
            <dt class="font-medium">{{ __('Reason') }}</dt>
            <dd>{{ $event->reason }}</dd>
        </div>
    @endif
</dl>
