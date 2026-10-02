<?php

use App\Domain\Platform\Models\PlatformAuditEvent;
use App\Livewire\DataTable\Column;
use App\Livewire\DataTable\Filter;
use App\Livewire\DataTable\WithDataTable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::platform')] #[Title('Platform audit')] class extends Component {
    use WithDataTable;

    private const int ACTION_FILTER_LIMIT = 200;

    /**
     * Events without a tenant: sign-ins, accounts and operator actions.
     *
     * @return Builder<PlatformAuditEvent>
     */
    protected function tableQuery(): Builder
    {
        return PlatformAuditEvent::query()->with('actor:id,name');
    }

    protected function tableColumns(): array
    {
        return [
            Column::make('occurred_at', __('When'))
                ->sortable()
                ->alwaysVisible()
                ->format(fn (PlatformAuditEvent $event): string => $event->occurred_at->format('Y-m-d H:i:s')),
            Column::make('actor', __('Who'))
                ->format(fn (PlatformAuditEvent $event): string => $event->actor->name ?? __('System')),
            Column::make('action', __('Action'))->sortable()->searchable()->monospace(),
            Column::make('subject_id', __('Subject'))->searchable()->monospace(),
            Column::make('source', __('Source'))->sortable(),
            Column::make('correlation_id', __('Correlation'))->searchable()->monospace()->hiddenByDefault(),
        ];
    }

    protected function tableFilters(): array
    {
        $actions = PlatformAuditEvent::query()->distinct()->orderBy('action')->limit(self::ACTION_FILTER_LIMIT)->pluck('action')->all();

        return [
            Filter::make('action', __('Action'), array_combine($actions, $actions)),
        ];
    }

    protected function tableKey(): string
    {
        return 'platform.audit';
    }

    protected function tableDefaultSort(): array
    {
        return ['occurred_at', 'desc'];
    }

    protected function tableCaption(): string
    {
        return __('Platform audit events');
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
    <x-mane::page-header :title="__('Platform audit')" :description="__('Sign-ins, accounts and every operator action, outside any space.')" />

    @include('mane.livewire.data-table')
</section>
