<?php

namespace App\Providers;

use App\Domain\Platform\Console\GrantOperatorCommand;
use App\Domain\Platform\Console\RevokeOperatorCommand;
use App\Domain\Platform\Errors\ImpersonationReadOnly;
use App\Domain\Platform\Http\Middleware\EnsureOperatorMfa;
use App\Domain\Platform\Http\Middleware\EnsurePlatformOperator;
use App\Domain\Platform\ImpersonationSession;
use Illuminate\Support\ServiceProvider;
use Livewire\Component;
use Livewire\Livewire;

class PlatformServiceProvider extends ServiceProvider
{
    /**
     * Livewire methods that only change what is displayed, so they stay usable while impersonating.
     */
    private const array READ_ONLY_METHODS = [
        'sortBy', 'toggleColumn', 'toggleRow', 'resetTable', 'applyView', 'clearSelection', 'togglePageSelection',
        'nextPage', 'previousPage', 'gotoPage', 'setPage', 'resetPage',
    ];

    public function boot(): void
    {
        Livewire::addPersistentMiddleware([EnsurePlatformOperator::class, EnsureOperatorMfa::class]);

        // Impersonation is read-only: every other component method is refused by default.
        Livewire::listen('call', function (Component $component, string $method): void {
            if (str_starts_with($method, '$') || in_array($method, self::READ_ONLY_METHODS, true)) {
                return;
            }

            $impersonation = $this->app->make(ImpersonationSession::class);

            if ($impersonation->isActive()) {
                try {
                    $impersonation->refuse($component->getName().'::'.$method);
                } catch (ImpersonationReadOnly $refusal) {
                    // Inside a component call only an HTTP exception reaches the browser with its status.
                    abort($refusal->status(), $refusal->getMessage());
                }
            }
        });

        if ($this->app->runningInConsole()) {
            $this->commands([GrantOperatorCommand::class, RevokeOperatorCommand::class]);
        }
    }
}
