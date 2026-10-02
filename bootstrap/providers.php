<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\ObservabilityServiceProvider;
use App\Providers\TallStackUiServiceProvider;
use App\Providers\TenancyServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    HorizonServiceProvider::class,
    ObservabilityServiceProvider::class,
    TallStackUiServiceProvider::class,
    TenancyServiceProvider::class,
];
