<?php

namespace Tests\Feature\ManeUi\Fixtures;

use App\Domain\Tenancy\Models\Workspace;
use App\Livewire\DataTable\BulkAction;
use App\Livewire\DataTable\Column;
use App\Livewire\DataTable\DataTable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * A minimal table with a bulk action, to exercise the DataTable contract.
 */
final class WorkspaceTable extends DataTable
{
    /** @var list<string> */
    public array $archived = [];

    /**
     * @return Builder<Workspace>
     */
    protected function tableQuery(): Builder
    {
        return Workspace::query();
    }

    protected function tableColumns(): array
    {
        return [
            Column::make('name', 'Name')->sortable()->searchable()->alwaysVisible(),
            Column::make('created_at', 'Created')->hiddenByDefault(),
        ];
    }

    protected function tableBulkActions(): array
    {
        return [
            BulkAction::make('archive', 'Archive', function (Collection $rows): void {
                $this->archived = $rows->map(fn (Model $row): string => (string) $row->getKey())->sort()->values()->all();
            }),
        ];
    }

    protected function tableKey(): string
    {
        return 'tests.workspaces';
    }

    protected function tableDefaultSort(): array
    {
        return ['name', 'asc'];
    }

    protected function tableCaption(): string
    {
        return 'Workspaces';
    }

    public function render(): View
    {
        return view('mane.livewire.data-table');
    }
}
