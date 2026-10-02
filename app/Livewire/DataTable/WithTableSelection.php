<?php

namespace App\Livewire\DataTable;

/**
 * Row selection and bulk actions of a DataTable (todo/todo.md §282); part of WithDataTable.
 */
trait WithTableSelection
{
    public const int BULK_LIMIT = 100;

    /** @var list<string> */
    public array $selected = [];

    /**
     * @return list<BulkAction>
     */
    protected function tableBulkActions(): array
    {
        return [];
    }

    public function togglePageSelection(): void
    {
        $pageKeys = $this->pageKeys();
        $everySelected = $pageKeys !== [] && array_diff($pageKeys, $this->selected) === [];

        $this->selected = $everySelected
            ? array_values(array_diff($this->selected, $pageKeys))
            : array_values(array_unique([...$this->selected, ...$pageKeys]));

        $this->normalizeTableState();
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function runBulkAction(string $key): void
    {
        $action = array_find($this->tableBulkActions(), fn (BulkAction $action): bool => $action->key === $key);

        abort_if($action === null, 404);

        if ($this->selected === []) {
            return;
        }

        $rows = $this->tableQuery()
            ->whereKey(array_slice($this->selected, 0, self::BULK_LIMIT))
            ->limit(self::BULK_LIMIT)
            ->get();

        $action->run($rows);

        $this->selected = [];
        unset($this->tableRows);
    }

    /**
     * @return list<BulkAction>
     */
    public function declaredBulkActions(): array
    {
        return $this->tableBulkActions();
    }

    public function pageIsFullySelected(): bool
    {
        $pageKeys = $this->pageKeys();

        return $pageKeys !== [] && array_diff($pageKeys, $this->selected) === [];
    }

    /**
     * @return list<string>
     */
    private function pageKeys(): array
    {
        $keys = [];

        foreach ($this->tableRows->items() as $row) {
            $key = $row->getKey();

            if (is_int($key) || is_string($key)) {
                $keys[] = (string) $key;
            }
        }

        return $keys;
    }
}
