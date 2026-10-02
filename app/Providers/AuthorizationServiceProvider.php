<?php

namespace App\Providers;

use App\Domain\Authorization\Actions\SyncPermissions;
use App\Domain\Authorization\Console\SyncPermissionsCommand;
use App\Domain\Authorization\PermissionRegistry;
use App\Domain\Authorization\PolicyEngine;
use App\Models\User;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthorizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PermissionRegistry::class);
        $this->app->scoped(PolicyEngine::class);
    }

    public function boot(PermissionRegistry $registry): void
    {
        // Views and policies ask `@can('tenancy.member.invite')`; the answer always comes from the Policy Engine.
        foreach ($registry->all() as $key => $permission) {
            Gate::define($key, fn (User $user): bool => $this->app->make(PolicyEngine::class)->decide($user, $permission)->allows());
        }

        if ($this->app->runningInConsole()) {
            $this->commands([SyncPermissionsCommand::class]);

            // Migrations run as the table owner: the catalogue follows every schema change, including migrate:fresh.
            Event::listen(function (MigrationsEnded $event): void {
                if ($event->method === 'up') {
                    $this->app->make(SyncPermissions::class)();
                }
            });
        }
    }
}
