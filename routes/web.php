<?php

use App\Domain\Tenancy\Actions\ResolveEntryWorkspace;
use App\Domain\Tenancy\Http\Middleware\ResolveWorkspace;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', function (Request $request, ResolveEntryWorkspace $resolveEntryWorkspace): RedirectResponse {
        $user = $request->user();
        $workspace = $user instanceof User ? $resolveEntryWorkspace($user) : null;

        return $workspace === null
            ? redirect()->route('onboarding')
            : redirect()->route('workspace.home', $workspace);
    })->name('dashboard');

    Route::livewire('onboarding', 'pages::onboarding.tenant')->name('onboarding');

    Route::middleware(ResolveWorkspace::class)->prefix('w/{workspace}')->group(function (): void {
        Route::livewire('/', 'pages::workspaces.home')->name('workspace.home');
        Route::livewire('workspaces/create', 'pages::workspaces.create')->name('workspace.create');
        Route::livewire('settings', 'pages::workspaces.settings')->name('workspace.settings');
        Route::livewire('tenant/settings', 'pages::tenants.settings')->name('tenant.settings');
        Route::livewire('audit', 'pages::audit.index')->name('audit.index');
    });
});

require __DIR__.'/settings.php';
