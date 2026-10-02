<?php

namespace App\Livewire\Concerns;

use TallStackUi\Traits\Interactions;

/**
 * Server half of the Livewire form contract (todo/todo.md §281); the view half is
 * <x-mane::form>.
 *
 * Validation and authorisation stay in the application actions the form calls;
 * this trait gives every form the same success and reset behaviour.
 */
trait HasFormContract
{
    use Interactions;

    /**
     * Put the fields back to their persisted values.
     */
    abstract protected function fillForm(): void;

    public function resetForm(): void
    {
        $this->resetValidation();
        $this->fillForm();
    }

    protected function formSucceeded(string $message): void
    {
        $this->resetValidation();
        $this->toast()->success($message)->send();
    }
}
