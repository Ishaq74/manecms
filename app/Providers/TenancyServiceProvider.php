<?php

namespace App\Providers;

use App\Domain\Authorization\Http\Middleware\EnsureTenantMfa;
use App\Domain\Tenancy\Console\VerifyRowSecurity;
use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Database\TenantDatabaseContext;
use App\Domain\Tenancy\Http\Middleware\ResolveWorkspace;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class TenancyServiceProvider extends ServiceProvider
{
    /**
     * Commands that change the schema or write fixtures, run as the table owner.
     */
    private const array OWNER_COMMANDS = [
        'authorization:sync-permissions', 'db:seed', 'db:wipe', 'migrate', 'migrate:fresh', 'migrate:install',
        'migrate:refresh', 'migrate:reset', 'migrate:rollback', 'migrate:status',
        'platform:grant-operator', 'platform:revoke-operator',
    ];

    private ?string $connectionBeforeOwnerCommand = null;

    public function register(): void
    {
        $this->app->scoped(TenantDatabaseContext::class);
        $this->app->scoped(TenantContext::class);
    }

    public function boot(): void
    {
        // Livewire update requests replay this middleware, so actions run inside the same tenant context.
        Livewire::addPersistentMiddleware([ResolveWorkspace::class, EnsureTenantMfa::class]);

        if ($this->app->runningInConsole()) {
            $this->commands([VerifyRowSecurity::class]);
            $this->runSchemaCommandsAsOwner();
        }
    }

    private function runSchemaCommandsAsOwner(): void
    {
        $owner = config('database.connections.pgsql_owner.username');

        if (! is_string($owner) || $owner === '') {
            return;
        }

        Event::listen(function (CommandStarting $event): void {
            if (in_array($event->command, self::OWNER_COMMANDS, true) && $this->connectionBeforeOwnerCommand === null) {
                $this->connectionBeforeOwnerCommand = DB::getDefaultConnection();
                DB::setDefaultConnection('pgsql_owner');
            }
        });

        Event::listen(function (CommandFinished $event): void {
            if (in_array($event->command, self::OWNER_COMMANDS, true) && $this->connectionBeforeOwnerCommand !== null) {
                DB::setDefaultConnection($this->connectionBeforeOwnerCommand);
                $this->connectionBeforeOwnerCommand = null;
            }
        });
    }
}
