<?php

use App\Domain\Audit\Models\AuditEvent;
use Illuminate\Contracts\Pagination\Paginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::sidebar')] #[Title('Audit')] class extends Component {
    use WithPagination;

    #[Url]
    public string $action = '';

    #[Url]
    public string $correlation = '';

    public ?string $openEventId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', AuditEvent::class);
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function toggle(string $eventId): void
    {
        $this->openEventId = $this->openEventId === $eventId ? null : $eventId;
    }

    /**
     * Row level security limits every query to the current tenant.
     *
     * @return Paginator<int, AuditEvent>
     */
    #[Computed]
    public function events(): Paginator
    {
        return AuditEvent::query()
            ->with('actor:id,name')
            ->when($this->action !== '', fn ($query) => $query->where('action', $this->action))
            ->when($this->correlation !== '', fn ($query) => $query->where('correlation_id', trim($this->correlation)))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->simplePaginate(25);
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function actions(): array
    {
        return AuditEvent::query()->distinct()->orderBy('action')->pluck('action')->all();
    }
}; ?>

<section class="flex w-full flex-col gap-6">
    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-semibold tracking-tight text-dark-900 dark:text-white">{{ __('Audit') }}</h1>

        <p class="text-sm text-dark-500 dark:text-dark-400">
            {{ __('Who did what, when, and what changed in this space.') }}
        </p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="flex flex-col gap-1">
            <label for="audit-action" class="text-sm font-medium text-dark-700 dark:text-dark-300">{{ __('Action') }}</label>

            <select
                id="audit-action"
                wire:model.live="action"
                data-test="audit-action-filter"
                class="rounded-md border border-dark-200 bg-white px-3 py-2 text-sm dark:border-dark-700 dark:bg-dark-800"
            >
                <option value="">{{ __('All actions') }}</option>
                @foreach ($this->actions as $actionName)
                    <option value="{{ $actionName }}" wire:key="action-{{ $actionName }}">{{ $actionName }}</option>
                @endforeach
            </select>
        </div>

        <x-input wire:model.live.debounce.400ms="correlation" :label="__('Correlation')" data-test="audit-correlation-filter" />
    </div>

    <div class="overflow-x-auto rounded-xl border border-dark-200 dark:border-dark-700">
        <table class="w-full text-start text-sm">
            <thead class="bg-dark-50 text-dark-600 dark:bg-dark-800 dark:text-dark-300">
                <tr>
                    <th scope="col" class="px-4 py-2 text-start font-medium">{{ __('When') }}</th>
                    <th scope="col" class="px-4 py-2 text-start font-medium">{{ __('Who') }}</th>
                    <th scope="col" class="px-4 py-2 text-start font-medium">{{ __('Action') }}</th>
                    <th scope="col" class="px-4 py-2 text-start font-medium">{{ __('Source') }}</th>
                    <th scope="col" class="px-4 py-2 text-start font-medium"><span class="sr-only">{{ __('Details') }}</span></th>
                </tr>
            </thead>

            <tbody class="divide-y divide-dark-200 dark:divide-dark-700">
                @forelse ($this->events as $event)
                    <tr wire:key="event-{{ $event->id }}" data-test="audit-row">
                        <td class="whitespace-nowrap px-4 py-2">{{ $event->occurred_at->format('Y-m-d H:i:s') }}</td>
                        <td class="px-4 py-2">{{ $event->actor?->name ?? __('System') }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ $event->action }}</td>
                        <td class="px-4 py-2">{{ $event->source }}</td>
                        <td class="px-4 py-2 text-end">
                            <button
                                type="button"
                                wire:click="toggle('{{ $event->id }}')"
                                aria-expanded="{{ $openEventId === $event->id ? 'true' : 'false' }}"
                                class="cursor-pointer text-sm underline"
                            >
                                {{ __('Details') }}
                            </button>
                        </td>
                    </tr>

                    @if ($openEventId === $event->id)
                        <tr wire:key="event-{{ $event->id }}-details" data-test="audit-details">
                            <td colspan="5" class="bg-dark-50 px-4 py-3 dark:bg-dark-800/60">
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
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-dark-500 dark:text-dark-400" data-test="audit-empty">
                            {{ __('No audit event matches these filters.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $this->events->links() }}</div>
</section>
