<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthorizationServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\IdentityServiceProvider;
use App\Providers\ObservabilityServiceProvider;
use App\Providers\TallStackUiServiceProvider;
use App\Providers\TenancyServiceProvider;

return [
    AppServiceProvider::class,
    AuthorizationServiceProvider::class,
    FortifyServiceProvider::class,
    HorizonServiceProvider::class,
    IdentityServiceProvider::class,
    ObservabilityServiceProvider::class,
    TallStackUiServiceProvider::class,
    TenancyServiceProvider::class,
];
