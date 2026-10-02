<?php

namespace App\Livewire\DataTable;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Rich table contract for Livewire components (todo/todo.md §42, §282, §485).
 *
 * Every value that reaches SQL comes from a declaration: a sort column must be
 * declared sortable, a filter value must be one of its options, a page size
 * must be in PAGE_SIZES. Anything else coming from the browser or the URL is
 * dropped. Rows are always paginated, never loaded whole.
 *
 * Render the table with `@include('mane.livewire.data-table')`.
 */
trait WithDataTable
{
    use WithPagination;
    use WithSavedViews;
    use WithTableSelection;

    public const array PAGE_SIZES = [10, 25, 50, 100];

    public const array DENSITIES = ['comfortable', 'compact', 'dense'];

    public const int SEARCH_MAX_LENGTH = 100;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $sort = '';

    #[Url(except: 'desc')]
    public string $direction = 'desc';

    /** @var array<string, string> */
    #[Url(except: [])]
    public array $filters = [];

    #[Url(as: 'per_page', except: 25)]
    public int $perPage = 25;

    /** @var list<string> */
    public array $hiddenColumns = [];

    public string $density = 'comfortable';

    public ?string $expandedRow = null;

    /**
     * The base query, already scoped and authorised; row level security applies on top.
     *
     * @return Builder<Model>
     */
    abstract protected function tableQuery(): Builder;

    /**
     * @return list<Column>
     */
    abstract protected function tableColumns(): array;

    /**
     * Stable identifier of the table, used by saved views.
     */
    abstract protected function tableKey(): string;

    /**
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    abstract protected function tableDefaultSort(): array;

    abstract protected function tableCaption(): string;

    /**
     * @return list<Filter>
     */
    protected function tableFilters(): array
    {
        return [];
    }

    /**
     * Skips the count query, for tables that can grow without bound.
     */
    protected function tableUsesSimplePagination(): bool
    {
        return false;
    }

    /**
     * Whether rows expand to show tableRowDetail().
     */
    protected function tableRowsExpand(): bool
    {
        return false;
    }

    protected function tableRowDetail(Model $row): ?Htmlable
    {
        return null;
    }

    public function mountWithDataTable(): void
    {
        $this->hiddenColumns = array_values(array_map(
            fn (Column $column): string => $column->key,
            array_filter($this->tableColumns(), fn (Column $column): bool => $column->isHiddenByDefault()),
        ));

        $this->normalizeTableState();
    }

    public function updatedWithDataTable(string $property): void
    {
        $this->normalizeTableState();

        if (in_array(Str::before($property, '.'), ['search', 'filters', 'perPage', 'sort', 'direction'], true)) {
            $this->resetPage();
            $this->selected = [];
            $this->activeView = null;
        }
    }

    /**
     * @return Paginator<int, Model>
     */
    #[Computed]
    public function tableRows(): Paginator
    {
        $query = $this->tableQuery();

        $searchable = array_map(fn (Column $column): string => $column->key, array_filter($this->tableColumns(), fn (Column $column): bool => $column->isSearchable()));

        if ($this->search !== '' && $searchable !== []) {
            $query->whereAny(array_values($searchable), 'ilike', '%'.addcslashes($this->search, '%_\\').'%');
        }

        foreach ($this->tableFilters() as $filter) {
            $value = $this->filters[$filter->key] ?? null;

            if ($filter->accepts($value)) {
                $filter->apply($query, (string) $value);
            }
        }

        [$column, $direction] = $this->currentTableSort();

        $query->orderBy($column, $direction)->orderBy($query->getModel()->getQualifiedKeyName(), $direction);

        return $this->tableUsesSimplePagination()
            ? $query->simplePaginate($this->perPage)
            : $query->paginate($this->perPage);
    }

    public function sortBy(string $key): void
    {
        if (! in_array($key, $this->sortableKeys(), true)) {
            return;
        }

        if ($this->sort === $key) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $key;
            $this->direction = 'asc';
        }

        $this->updatedWithDataTable('sort');
    }

    public function toggleColumn(string $key): void
    {
        $this->hiddenColumns = in_array($key, $this->hiddenColumns, true)
            ? array_values(array_diff($this->hiddenColumns, [$key]))
            : [...$this->hiddenColumns, $key];

        $this->normalizeTableState();
        $this->activeView = null;
    }

    public function toggleRow(string $key): void
    {
        $this->expandedRow = $this->expandedRow === $key ? null : $key;
    }

    public function resetTable(): void
    {
        $this->reset('search', 'sort', 'direction', 'filters', 'perPage', 'selected', 'density', 'expandedRow', 'activeView');
        $this->mountWithDataTable();
        $this->resetPage();
    }

    /**
     * @return list<Column>
     */
    public function visibleTableColumns(): array
    {
        return array_values(array_filter($this->tableColumns(), fn (Column $column): bool => ! in_array($column->key, $this->hiddenColumns, true)));
    }

    /**
     * @return list<Column>
     */
    public function hideableTableColumns(): array
    {
        return array_values(array_filter($this->tableColumns(), fn (Column $column): bool => $column->isHideable()));
    }

    /**
     * @return list<Filter>
     */
    public function declaredTableFilters(): array
    {
        return $this->tableFilters();
    }

    /**
     * The sort in effect: the requested one when declared, the default otherwise.
     *
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    public function currentTableSort(): array
    {
        if (! in_array($this->sort, $this->sortableKeys(), true)) {
            return $this->tableDefaultSort();
        }

        return [$this->sort, $this->direction === 'asc' ? 'asc' : 'desc'];
    }

    public function tableHasRowDetail(): bool
    {
        return $this->tableRowsExpand();
    }

    public function tableRowDetailFor(Model $row): ?Htmlable
    {
        return $this->tableRowDetail($row);
    }

    public function tableCaptionText(): string
    {
        return $this->tableCaption();
    }

    public function tableIsFiltered(): bool
    {
        return $this->search !== '' || $this->filters !== [];
    }

    /**
     * Drop every value that was not declared by the table.
     */
    protected function normalizeTableState(): void
    {
        if (! in_array($this->sort, $this->sortableKeys(), true)) {
            $this->sort = '';
        }

        $this->direction = $this->direction === 'asc' ? 'asc' : 'desc';
        $this->search = mb_substr(trim($this->search), 0, self::SEARCH_MAX_LENGTH);

        $declaredFilters = [];

        foreach ($this->tableFilters() as $filter) {
            if ($filter->accepts($this->filters[$filter->key] ?? null)) {
                $declaredFilters[$filter->key] = $this->filters[$filter->key];
            }
        }

        $this->filters = $declaredFilters;

        if (! in_array($this->perPage, self::PAGE_SIZES, true)) {
            $this->perPage = 25;
        }

        if (! in_array($this->density, self::DENSITIES, true)) {
            $this->density = 'comfortable';
        }

        $hideable = array_map(fn (Column $column): string => $column->key, $this->hideableTableColumns());
        $this->hiddenColumns = array_values(array_unique(array_intersect($this->hiddenColumns, $hideable)));

        if (count($this->hiddenColumns) >= count($this->tableColumns())) {
            $this->hiddenColumns = [];
        }

        $this->selected = array_slice(array_values(array_unique(array_filter($this->selected, 'is_string'))), 0, self::BULK_LIMIT);
    }

    /**
     * @return list<string>
     */
    private function sortableKeys(): array
    {
        return array_values(array_map(fn (Column $column): string => $column->key, array_filter($this->tableColumns(), fn (Column $column): bool => $column->isSortable())));
    }
}
