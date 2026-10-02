{{--
    View of App\Livewire\DataTable\WithDataTable, included by the component view:
    @include('mane.livewire.data-table')
--}}
@php
    /** @var Illuminate\Contracts\Pagination\Paginator<int, Illuminate\Database\Eloquent\Model> $rows */
    $rows = $this->tableRows;
    $columns = $this->visibleTableColumns();
    $bulkActions = $this->declaredBulkActions();
    $selectable = $bulkActions !== [];
    $expandable = $this->tableHasRowDetail();
    $colspan = count($columns) + ($selectable ? 1 : 0) + ($expandable ? 1 : 0);
    [$sortedKey, $sortedDirection] = $this->currentTableSort();
    $savedViewsEnabled = app(App\Domain\Tenancy\Context\TenantContext::class)->isInstalled();
@endphp

<div class="flex flex-col gap-4" data-density="{{ $this->density }}" data-test="data-table">
    <div class="flex flex-wrap items-end gap-3">
        <div class="min-w-48 flex-1">
            <x-mane::input
                type="search"
                wire:model.live.debounce.400ms="search"
                :label="__('Search')"
                icon="magnifying-glass"
                maxlength="100"
                data-test="data-table-search"
            />
        </div>

        @foreach ($this->declaredTableFilters() as $filter)
            <div class="w-full sm:w-56" wire:key="filter-{{ $filter->key }}">
                <x-mane::select
                    wire:model.live="filters.{{ $filter->key }}"
                    :label="$filter->label"
                    :options="$filter->options"
                    :placeholder="__('All')"
                    data-test="data-table-filter-{{ $filter->key }}"
                />
            </div>
        @endforeach

        <x-mane::popover :label="__('Display')" icon="adjustments-horizontal" align="end" data-test="data-table-display">
            <div class="flex flex-col gap-4">
                <fieldset class="flex flex-col gap-2">
                    <legend class="mb-1 text-xs font-semibold uppercase tracking-wide text-fg-muted">{{ __('Columns') }}</legend>

                    @foreach ($this->hideableTableColumns() as $column)
                        <label class="flex cursor-pointer items-center gap-2" wire:key="column-toggle-{{ $column->key }}">
                            <input
                                type="checkbox"
                                class="rounded border-line-strong text-primary-600 focus:ring-focus"
                                wire:click="toggleColumn('{{ $column->key }}')"
                                @checked(! in_array($column->key, $this->hiddenColumns, true))
                                data-test="data-table-column-{{ $column->key }}"
                            />
                            <span>{{ $column->label }}</span>
                        </label>
                    @endforeach
                </fieldset>

                <x-mane::select
                    wire:model.live="density"
                    :label="__('Density')"
                    :options="['comfortable' => __('Comfortable'), 'compact' => __('Compact'), 'dense' => __('Dense')]"
                    data-test="data-table-density"
                />

                <x-mane::select
                    wire:model.live="perPage"
                    :label="__('Rows per page')"
                    :options="array_combine($this::PAGE_SIZES, $this::PAGE_SIZES)"
                    data-test="data-table-per-page"
                />
            </div>
        </x-mane::popover>

        @if ($savedViewsEnabled)
            <x-mane::popover :label="__('Views')" icon="bookmark" align="end" data-test="data-table-views">
                <div class="flex flex-col gap-3">
                    @forelse ($this->tableSavedViews as $view)
                        <div class="flex items-center gap-2" wire:key="saved-view-{{ $view->id }}">
                            <x-mane::button
                                variant="ghost"
                                size="sm"
                                class="flex-1 justify-start"
                                wire:click="applyView('{{ $view->id }}')"
                                aria-pressed="{{ $this->activeView === $view->id ? 'true' : 'false' }}"
                                :text="$view->name"
                                data-test="data-table-apply-view"
                            />

                            <x-mane::icon-button
                                icon="trash"
                                size="sm"
                                :label="__('Delete the view :name', ['name' => $view->name])"
                                wire:click="deleteView('{{ $view->id }}')"
                                wire:confirm="{{ __('Delete this view?') }}"
                                data-test="data-table-delete-view"
                            />
                        </div>
                    @empty
                        <p class="text-fg-muted">{{ __('No saved view yet.') }}</p>
                    @endforelse

                    <form wire:submit="saveView" class="flex items-end gap-2 border-t border-line pt-3">
                        <div class="flex-1">
                            <x-mane::input wire:model="viewName" :label="__('Save the current view as')" maxlength="80" data-test="data-table-view-name" />
                        </div>

                        <x-mane::button type="submit" size="sm" loading="saveView" :text="__('Save')" data-test="data-table-save-view" />
                    </form>
                </div>
            </x-mane::popover>
        @endif

        @if ($this->tableIsFiltered() || $this->sort !== '')
            <x-mane::button variant="ghost" size="sm" icon="x-mark" wire:click="resetTable" :text="__('Reset')" data-test="data-table-reset" />
        @endif
    </div>

    @if ($selectable && $this->selected !== [])
        <div role="region" aria-label="{{ __('Bulk actions') }}" class="flex flex-wrap items-center gap-3 rounded-surface bg-surface-sunken px-4 py-2 text-sm" data-test="data-table-bulk-bar">
            <span class="font-medium">{{ trans_choice('{1} :count row selected|[2,*] :count rows selected', count($this->selected)) }}</span>

            @foreach ($bulkActions as $action)
                @if ($action->isDestructive())
                    <x-mane::button
                        size="sm"
                        variant="danger"
                        wire:click="runBulkAction('{{ $action->key }}')"
                        wire:confirm="{{ $action->confirmation() }}"
                        loading="runBulkAction"
                        :text="$action->label"
                        wire:key="bulk-{{ $action->key }}"
                        data-test="data-table-bulk-{{ $action->key }}"
                    />
                @else
                    <x-mane::button
                        size="sm"
                        variant="secondary"
                        wire:click="runBulkAction('{{ $action->key }}')"
                        loading="runBulkAction"
                        :text="$action->label"
                        wire:key="bulk-{{ $action->key }}"
                        data-test="data-table-bulk-{{ $action->key }}"
                    />
                @endif
            @endforeach

            <x-mane::button variant="ghost" size="sm" wire:click="clearSelection" :text="__('Clear selection')" />
        </div>
    @endif

    <div class="overflow-x-auto rounded-surface border border-line">
        <table class="w-full text-start text-sm text-fg" wire:loading.delay.class="opacity-60">
            <caption class="sr-only">{{ $this->tableCaptionText() }}</caption>

            <thead class="bg-surface-sunken text-fg-muted">
                <tr>
                    @if ($selectable)
                        <th scope="col" class="w-10 px-cell-x py-cell-y">
                            <input
                                type="checkbox"
                                class="rounded border-line-strong text-primary-600 focus:ring-focus"
                                aria-label="{{ __('Select every row on this page') }}"
                                wire:click="togglePageSelection"
                                @checked($this->pageIsFullySelected())
                                data-test="data-table-select-page"
                            />
                        </th>
                    @endif

                    @forelse ($columns as $column)
                        @php
                            $sorted = $column->isSortable() && $sortedKey === $column->key;
                        @endphp

                        <th
                            scope="col"
                            class="px-cell-x py-cell-y text-start font-medium whitespace-nowrap"
                            @if ($column->isSortable()) aria-sort="{{ $sorted ? ($sortedDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}" @endif
                            wire:key="column-{{ $column->key }}"
                        >
                            @if ($column->isSortable())
                                <button
                                    type="button"
                                    wire:click="sortBy('{{ $column->key }}')"
                                    class="inline-flex cursor-pointer items-center gap-1 rounded-control hover:text-fg"
                                    data-test="data-table-sort-{{ $column->key }}"
                                >
                                    {{ $column->label }}
                                    <x-mane::icon :name="$sorted ? ($sortedDirection === 'asc' ? 'chevron-up' : 'chevron-down') : 'chevron-up-down'" class="size-4" />
                                </button>
                            @else
                                {{ $column->label }}
                            @endif
                        </th>
                    @empty
                        <th scope="col" class="px-cell-x py-cell-y"><span class="sr-only">{{ $this->tableCaptionText() }}</span></th>
                    @endforelse

                    @if ($expandable)
                        <th scope="col" class="px-cell-x py-cell-y"><span class="sr-only">{{ __('Details') }}</span></th>
                    @endif
                </tr>
            </thead>

            <tbody class="divide-y divide-line">
                @forelse ($rows as $row)
                    @php
                        $rowKey = (string) $row->getKey();
                        $expanded = $expandable && $this->expandedRow === $rowKey;
                    @endphp

                    <tr wire:key="row-{{ $rowKey }}" @class(['bg-surface-sunken' => in_array($rowKey, $this->selected, true)]) data-test="data-table-row">
                        @if ($selectable)
                            <td class="px-cell-x py-cell-y">
                                <input
                                    type="checkbox"
                                    value="{{ $rowKey }}"
                                    wire:model.live="selected"
                                    class="rounded border-line-strong text-primary-600 focus:ring-focus"
                                    aria-label="{{ __('Select row :number', ['number' => $loop->iteration]) }}"
                                    data-test="data-table-select-row"
                                />
                            </td>
                        @endif

                        @foreach ($columns as $column)
                            <td @class(['px-cell-x py-cell-y align-top', 'font-mono text-xs' => $column->isMonospace()]) wire:key="cell-{{ $rowKey }}-{{ $column->key }}">{{ $column->value($row) }}</td>
                        @endforeach

                        @if ($expandable)
                            <td class="px-cell-x py-cell-y text-end">
                                <x-mane::button
                                    variant="ghost"
                                    size="sm"
                                    wire:click="toggleRow('{{ $rowKey }}')"
                                    aria-expanded="{{ $expanded ? 'true' : 'false' }}"
                                    aria-controls="row-detail-{{ $rowKey }}"
                                    :text="__('Details')"
                                    data-test="data-table-expand"
                                />
                            </td>
                        @endif
                    </tr>

                    @if ($expanded)
                        <tr wire:key="row-{{ $rowKey }}-detail" id="row-detail-{{ $rowKey }}" data-test="data-table-row-detail">
                            <td colspan="{{ $colspan }}" class="bg-surface-sunken px-cell-x py-cell-y">{{ $this->tableRowDetailFor($row) }}</td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="{{ $colspan }}">
                            <x-mane::empty-state :kind="$this->tableIsFiltered() ? 'no-results' : 'empty'" data-test="data-table-empty" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-mane::pagination :paginator="$rows" />
</div>
