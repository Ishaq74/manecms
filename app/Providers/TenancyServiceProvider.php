<?php

namespace App\Providers;

use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Http\Middleware\ResolveWorkspace;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(TenantContext::class);
    }

    public function boot(): void
    {
        // Livewire update requests replay this middleware, so actions run inside the same tenant context.
        Livewire::addPersistentMiddleware([ResolveWorkspace::class]);
    }
}
