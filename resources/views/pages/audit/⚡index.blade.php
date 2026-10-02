<?php

use App\Domain\Audit\Models\AuditEvent;
use App\Livewire\DataTable\Column;
use App\Livewire\DataTable\WithDataTable;
use App\Livewire\DataTable\Filter;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::sidebar')] #[Title('Audit')] class extends Component {
    use WithDataTable;

    private const int ACTION_FILTER_LIMIT = 200;

    public function mount(): void
    {
        $this->authorize('viewAny', AuditEvent::class);
    }

    /**
     * Row level security limits every query to the current tenant.
     *
     * @return Builder<AuditEvent>
     */
    protected function tableQuery(): Builder
    {
        $this->authorize('viewAny', AuditEvent::class);

        return AuditEvent::query()->with('actor:id,name');
    }

    protected function tableColumns(): array
    {
        return [
            Column::make('occurred_at', __('When'))
                ->sortable()
                ->alwaysVisible()
                ->format(fn (AuditEvent $event): string => $event->occurred_at->format('Y-m-d H:i:s')),
            Column::make('actor', __('Who'))
                ->format(fn (AuditEvent $event): string => $event->actor->name ?? __('System')),
            Column::make('action', __('Action'))->sortable()->searchable()->monospace(),
            Column::make('source', __('Source'))->sortable(),
            Column::make('correlation_id', __('Correlation'))->searchable()->monospace()->hiddenByDefault(),
        ];
    }

    protected function tableFilters(): array
    {
        $actions = AuditEvent::query()->distinct()->orderBy('action')->limit(self::ACTION_FILTER_LIMIT)->pluck('action')->all();
            
            return [
            Filter::make('action', __('Action'), array_combine($actions, $actions)),
            Filter::make('source', __('Source'), ['http' => 'http', 'cli' => 'cli', 'queue' => 'queue', 'api' => 'api', 'ai' => 'ai']),
            ];
    }

    protected function tableKey(): string
    {
        return 'audit.events';
    }

protected function tableDefaultSort(): array
    {
        return ['occurred_at', 'desc'];
}

    protected function tableCaption(): string
        {
            return __('Audit events of this space');
    }

            protected function tableUsesSimplePagination(): bool
                {
                return true;
                }

        protected function tableRowsExpand(): bool
    {
        return true;
    }

    protected function tableRowDetail(Model $row): ?Htmlable
        {
            return new HtmlString(view('partials.audit-event-detail', ['event' => $row])->render());
                }
                    }; ?>

            <section class="flex w-full flex-col gap-6">
                <x-mane::page-header :title="__('Audit')" :description="__('Who did what, when, and what changed in this space.')" />

    @include('mane.livewire.data-table')
</section>
