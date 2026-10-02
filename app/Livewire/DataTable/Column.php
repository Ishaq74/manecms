<?php

namespace App\Livewire\DataTable;

use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

/**
 * A DataTable column. Only columns declared sortable or searchable ever reach SQL.
 */
final class Column
{
    private bool $sortable = false;

    private bool $searchable = false;

    private bool $hideable = true;

    private bool $hiddenByDefault = false;

    private bool $monospace = false;

    private ?Closure $format = null;

    private function __construct(
        public readonly string $key,
        public readonly string $label,
    ) {}

    public static function make(string $key, string $label): self
    {
        return new self($key, $label);
    }

    public function sortable(): self
    {
        $this->sortable = true;

        return $this;
    }

    public function searchable(): self
    {
        $this->searchable = true;

        return $this;
    }

    public function alwaysVisible(): self
    {
        $this->hideable = false;

        return $this;
    }

    public function hiddenByDefault(): self
    {
        $this->hiddenByDefault = true;

        return $this;
    }

    public function monospace(): self
    {
        $this->monospace = true;

        return $this;
    }

    /**
     * @param  Closure(never): (string|Htmlable|null)  $format  receives a row of the table's model
     */
    public function format(Closure $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function isHideable(): bool
    {
        return $this->hideable;
    }

    public function isHiddenByDefault(): bool
    {
        return $this->hiddenByDefault;
    }

    public function isMonospace(): bool
    {
        return $this->monospace;
    }

    public function value(Model $row): string|Htmlable|null
    {
        $value = $this->format instanceof Closure ? ($this->format)($row) : $row->getAttribute($this->key);

        if ($value instanceof Htmlable) {
            return $value;
        }

        return is_scalar($value) ? (string) $value : null;
    }
}
