<?php

namespace App\Livewire\DataTable;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * An action run on the selected rows. The rows are re-read through the table query,
 * so they never leave its scope; the handler still authorises what it does.
 */
final class BulkAction
{
    private bool $destructive = false;

    private ?string $confirmation = null;

    /**
     * @param  Closure(Collection<int, Model>): void  $handler
     */
    private function __construct(
        public readonly string $key,
        public readonly string $label,
        private readonly Closure $handler,
    ) {}

    /**
     * @param  Closure(Collection<int, Model>): void  $handler
     */
    public static function make(string $key, string $label, Closure $handler): self
    {
        return new self($key, $label, $handler);
    }

    public function destructive(string $confirmation): self
    {
        $this->destructive = true;
        $this->confirmation = $confirmation;

        return $this;
    }

    public function isDestructive(): bool
    {
        return $this->destructive;
    }

    public function confirmation(): ?string
    {
        return $this->confirmation;
    }

    /**
     * @param  Collection<int, Model>  $rows
     */
    public function run(Collection $rows): void
    {
        ($this->handler)($rows);
    }
}
