<?php

use Illuminate\Database\Eloquent\Model;

arch('domain logic does not depend on Livewire')
    ->expect('App\Domain')
    ->not->toUse('Livewire');

arch('domain enums are backed enums')
    ->expect('App\Domain\Tenancy\Enums')
    ->toBeStringBackedEnums();

arch('domain models are Eloquent models')
    ->expect('App\Domain\Tenancy\Models')
    ->toExtend(Model::class);
