<?php

namespace App\Providers;

use App\Domain\Identity\Actions\RecordSignInDevice;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class IdentityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(function (Login $event): void {
            if ($event->user instanceof User) {
                $this->app->make(RecordSignInDevice::class)($event->user);
            }
        });
    }
}
