<?php

namespace App\Livewire;

use App\Livewire\Concerns\HasFormContract;
use Livewire\Component;

/**
 * Base class of a Livewire form page; the contract lives in HasFormContract.
 */
abstract class FormComponent extends Component
{
    use HasFormContract;
}
