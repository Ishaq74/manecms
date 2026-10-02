<?php

namespace App\Livewire\DataTable;

use App\Domain\Platform\Models\SavedTableView;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

/**
 * Base class of a Livewire rich table; the contract lives in WithDataTable.
 *
 * @property-read Paginator<int, Model> $tableRows
 * @property-read Collection<int, SavedTableView> $tableSavedViews
 */
abstract class DataTable extends Component
{
    use WithDataTable;
}
