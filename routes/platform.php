<?php

use App\Domain\Platform\Http\Controllers\StopImpersonationController;
use App\Domain\Platform\Http\Middleware\EnsureOperatorMfa;
use App\Domain\Platform\Http\Middleware\EnsurePlatformOperator;
use Illuminate\Support\Facades\Route;

// No 'auth': it would send guests to the login page; the operator check answers 404 to everyone else.
Route::middleware([EnsurePlatformOperator::class, 'verified', EnsureOperatorMfa::class, 'password.confirm'])
    ->prefix('platform')
    ->name('platform.')
    ->group(function (): void {
        Route::livewire('/', 'pages::platform.dashboard')->name('dashboard');
        Route::livewire('tenants', 'pages::platform.tenants')->name('tenants.index');
        Route::livewire('tenants/{tenant}', 'pages::platform.tenant')->name('tenants.show');
        Route::livewire('audit', 'pages::platform.audit')->name('audit');
    });

Route::post('impersonation/stop', StopImpersonationController::class)
    ->middleware('auth')
    ->name('impersonation.stop');
