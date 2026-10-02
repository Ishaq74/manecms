<?php

namespace App\Livewire\DataTable;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A DataTable filter. Its value must be one of the declared options to be applied.
 */
final class Filter
{
    /** @var (Closure(Builder<Model>, string): void)|null */
    private ?Closure $apply = null;

    /**
     * @param  array<string, string>  $options  value => label
     */
    private function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly array $options,
    ) {}

    /**
     * @param  array<string, string>  $options  value => label
     */
    public static function make(string $key, string $label, array $options): self
    {
        return new self($key, $label, $options);
    }

    /**
     * @param  Closure(Builder<Model>, string): void  $apply
     */
    public function using(Closure $apply): self
    {
        $this->apply = $apply;

        return $this;
    }

    public function accepts(mixed $value): bool
    {
        return is_string($value) && $value !== '' && array_key_exists($value, $this->options);
    }

    /**
     * @param  Builder<Model>  $query
     */
    public function apply(Builder $query, string $value): void
    {
        if ($this->apply instanceof Closure) {
            ($this->apply)($query, $value);

            return;
        }

        $query->where($this->key, $value);
    }
}
