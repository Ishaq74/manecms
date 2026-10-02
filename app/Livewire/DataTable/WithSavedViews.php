<?php

namespace App\Livewire\DataTable;

use App\Domain\Platform\Actions\DeleteTableView;
use App\Domain\Platform\Actions\SaveTableView;
use App\Domain\Platform\Models\SavedTableView;
use App\Domain\Tenancy\Context\TenantContext;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

/**
 * Saved views of a DataTable: private to their author, kept per tenant by row level security; part of WithDataTable.
 */
trait WithSavedViews
{
    public string $viewName = '';

    public ?string $activeView = null;

    /**
     * @return Collection<int, SavedTableView>
     */
    #[Computed]
    public function tableSavedViews(): Collection
    {
        $user = Auth::user();

        if (! $user instanceof User || ! app(TenantContext::class)->isInstalled()) {
            return new Collection;
        }

        return SavedTableView::query()
            ->where('user_id', $user->id)
            ->where('table_key', $this->tableKey())
            ->orderBy('name')
            ->limit(SaveTableView::MAX_VIEWS_PER_TABLE)
            ->get(['id', 'name']);
    }

    public function saveView(SaveTableView $saveTableView): void
    {
        $view = $saveTableView($this->tableUser(), $this->tableKey(), $this->viewName, $this->tableState());

        $this->activeView = $view->id;
        $this->viewName = '';
        unset($this->tableSavedViews);
    }

    public function applyView(string $viewId): void
    {
        $view = SavedTableView::query()
            ->where('user_id', $this->tableUser()->id)
            ->where('table_key', $this->tableKey())
            ->find($viewId);

        if (! $view instanceof SavedTableView) {
            return;
        }

        $state = $view->state;
        $filters = [];

        foreach (is_array($state['filters'] ?? null) ? $state['filters'] : [] as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $filters[$key] = $value;
            }
        }

        $this->search = is_string($state['search'] ?? null) ? $state['search'] : '';
        $this->sort = is_string($state['sort'] ?? null) ? $state['sort'] : '';
        $this->direction = is_string($state['direction'] ?? null) ? $state['direction'] : 'desc';
        $this->filters = $filters;
        $this->perPage = is_int($state['perPage'] ?? null) ? $state['perPage'] : 25;
        $this->hiddenColumns = is_array($state['hiddenColumns'] ?? null) ? array_values(array_filter($state['hiddenColumns'], 'is_string')) : [];
        $this->density = is_string($state['density'] ?? null) ? $state['density'] : 'comfortable';

        $this->normalizeTableState();
        $this->resetPage();
        $this->selected = [];
        $this->activeView = $view->id;
    }

    public function deleteView(DeleteTableView $deleteTableView, string $viewId): void
    {
        $deleteTableView($this->tableUser(), $viewId);

        if ($this->activeView === $viewId) {
            $this->activeView = null;
        }

        unset($this->tableSavedViews);
    }

    /**
     * @return array{search: string, sort: string, direction: string, filters: array<string, string>, perPage: int, hiddenColumns: list<string>, density: string}
     */
    protected function tableState(): array
    {
        return [
            'search' => $this->search,
            'sort' => $this->sort,
            'direction' => $this->direction,
            'filters' => $this->filters,
            'perPage' => $this->perPage,
            'hiddenColumns' => $this->hiddenColumns,
            'density' => $this->density,
        ];
    }

    private function tableUser(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
