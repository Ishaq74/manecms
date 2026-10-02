<?php

use App\Domain\Platform\Models\PlatformTenant;
use App\Livewire\DataTable\Column;
use App\Livewire\DataTable\Filter;
use App\Livewire\DataTable\WithDataTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::platform')] #[Title('Tenants')] class extends Component {
    use WithDataTable;

    /**
     * The view returns rows to platform operators only.
     *
     * @return Builder<PlatformTenant>
     */
    protected function tableQuery(): Builder
    {
        return PlatformTenant::query();
    }

    protected function tableColumns(): array
    {
        return [
            Column::make('name', __('Space'))
                ->sortable()
                ->searchable()
                ->alwaysVisible()
                ->format(fn (PlatformTenant $tenant): HtmlString => new HtmlString(view('partials.platform-tenant-link', ['tenant' => $tenant])->render())),
            Column::make('owner_email', __('Owner'))->sortable()->searchable()
                ->format(fn (PlatformTenant $tenant): string => $tenant->owner_email === null ? '—' : "{$tenant->owner_name} ({$tenant->owner_email})"),
            Column::make('status', __('Status'))->sortable()
                ->format(fn (PlatformTenant $tenant): HtmlString => new HtmlString(view('partials.platform-tenant-status', ['status' => $tenant->status])->render())),
            Column::make('members_count', __('Members'))->sortable(),
            Column::make('workspaces_count', __('Workspaces'))->sortable(),
            Column::make('created_at', __('Created'))->sortable()
                ->format(fn (PlatformTenant $tenant): string => $tenant->created_at?->translatedFormat('j M Y') ?? ''),
        ];
    }

    protected function tableFilters(): array
    {
        return [
            Filter::make('status', __('Status'), [
                PlatformTenant::STATUS_ACTIVE => __('Active'),
                PlatformTenant::STATUS_SUSPENDED => __('Suspended'),
                PlatformTenant::STATUS_ARCHIVED => __('Archived'),
            ]),
        ];
    }

    protected function tableKey(): string
    {
        return 'platform.tenants';
    }

    protected function tableDefaultSort(): array
    {
        return ['created_at', 'desc'];
    }

    protected function tableCaption(): string
    {
        return __('Spaces hosted on the platform');
    }
}; ?>

<section class="flex w-full flex-col gap-6">
    <x-mane::page-header :title="__('Tenants')" :description="__('Every space hosted on the platform, with its owner and its size.')" />

    @include('mane.livewire.data-table')
</section>
