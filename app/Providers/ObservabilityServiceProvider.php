<?php

namespace App\Providers;

use App\Domain\Audit\AuditLog;
use App\Domain\Platform\Errors\DomainError;
use App\Domain\Platform\Observability\RequestContext;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Log\Context\Repository;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEvent;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Livewire\Component;
use Livewire\Livewire;
use TallStackUi\Interactions\Toast;
use Throwable;

/**
 * Correlation, audit of authentication events and rendering of business errors.
 */
class ObservabilityServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string<TwoFactorAuthenticationEvent|RecoveryCodesGenerated>, string>
     */
    private const array TWO_FACTOR_ACTIONS = [
        TwoFactorAuthenticationConfirmed::class => 'identity.two_factor.enabled',
        TwoFactorAuthenticationDisabled::class => 'identity.two_factor.disabled',
        TwoFactorAuthenticationFailed::class => 'identity.two_factor.failed',
        RecoveryCodesGenerated::class => 'identity.two_factor.recovery_codes_regenerated',
    ];

    public function boot(): void
    {
        $this->propagateCorrelationToJobs();
        $this->auditAuthentication();
        $this->toastBusinessErrors();
    }

    private function propagateCorrelationToJobs(): void
    {
        Context::dehydrating(function (Repository $context): void {
            $context->add(RequestContext::CAUSATION_ID, $context->get(RequestContext::REQUEST_ID));
        });

        Context::hydrated(function (Repository $context): void {
            $context->add(RequestContext::REQUEST_ID, (string) Str::ulid());
            $context->add(RequestContext::SOURCE, 'queue');
        });
    }

    private function auditAuthentication(): void
    {
        $audit = fn (): AuditLog => $this->app->make(AuditLog::class);

        Event::listen(function (Login $event) use ($audit): void {
            $audit()->record('identity.login.succeeded', $this->user($event->user), actorId: $this->userId($event->user), platform: true);
        });

        Event::listen(function (Failed $event) use ($audit): void {
            $email = $event->credentials['email'] ?? null;

            $audit()->record(
                'identity.login.failed',
                after: is_string($email) ? ['email' => Str::lower($email)] : [],
                actorId: $this->userId($event->user),
                platform: true,
            );
        });

        Event::listen(function (Logout $event) use ($audit): void {
            $audit()->record('identity.logout', $this->user($event->user), actorId: $this->userId($event->user), platform: true);
        });

        foreach (self::TWO_FACTOR_ACTIONS as $event => $action) {
            Event::listen($event, function (TwoFactorAuthenticationEvent|RecoveryCodesGenerated $event) use ($audit, $action): void {
                $audit()->record($action, $this->user($event->user), actorId: $this->userId($event->user), platform: true);
            });
        }

        User::updated(function (User $user) use ($audit): void {
            if ($user->wasChanged('password')) {
                $audit()->record('identity.password.changed', $user, actorId: $user->id, platform: true);
            }
        });
    }

    private function toastBusinessErrors(): void
    {
        Livewire::listen('exception', function (Component $component, Throwable $exception, callable $stopPropagation): void {
            if (! $exception instanceof DomainError) {
                return;
            }

            report($exception);

            (new Toast($component))
                ->error(__('Action not completed'), $exception->getMessage().' '.__('Reference: :reference', [
                    'reference' => RequestContext::correlationId(),
                ]))
                ->send();

            $stopPropagation();
        });
    }

    private function user(mixed $user): ?User
    {
        return $user instanceof User ? $user : null;
    }

    private function userId(mixed $user): ?int
    {
        return $user instanceof User ? $user->id : null;
    }
}
