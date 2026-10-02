<?php

use App\Domain\Platform\Errors\DomainError;
use Illuminate\Support\Facades\Route;
use Livewire\Component;
use Livewire\Livewire;

final class SlotAlreadyTaken extends DomainError
{
    public function __construct()
    {
        parent::__construct('The requested slot is no longer available.');
    }

    public function errorCode(): string
    {
        return 'BOOKING_CONFLICT';
    }

    public function status(): int
    {
        return 409;
    }
}

final class ReservesASlot extends Component
{
    public function reserve(): void
    {
        throw new SlotAlreadyTaken;
    }

    public function render(): string
    {
        return '<div><button wire:click="reserve">Reserve</button></div>';
    }
}

beforeEach(function (): void {
    Route::middleware('web')->get('/__domain-error', fn () => throw new SlotAlreadyTaken);
});

it('answers API clients with the error contract', function (): void {
    $this->getJson('/__domain-error', ['X-Correlation-ID' => 'contract-trace-01'])
        ->assertStatus(409)
        ->assertExactJson([
            'code' => 'BOOKING_CONFLICT',
            'message' => 'The requested slot is no longer available.',
            'detail' => null,
            'correlation_id' => 'contract-trace-01',
            'retryable' => false,
            'field_errors' => [],
        ]);
});

it('shows people a page with the message and a reference', function (): void {
    $this->get('/__domain-error', ['X-Correlation-ID' => 'page-trace-0001'])
        ->assertStatus(409)
        ->assertSee('The requested slot is no longer available.')
        ->assertSee('page-trace-0001');
});

it('turns a business error raised in a component into an error toast', function (): void {
    Livewire::test(ReservesASlot::class)
        ->call('reserve')
        ->assertDispatched('ts-ui:toast');
});
