<?php

use App\Domain\Platform\Errors\DomainError;
use App\Domain\Platform\Http\Middleware\AssignCorrelationId;
use App\Domain\Platform\Http\Middleware\GuardImpersonation;
use App\Domain\Platform\Observability\RequestContext;
use App\Domain\Tenancy\Http\Middleware\ScopeDatabaseSession;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(AssignCorrelationId::class);
        $middleware->web(append: [ScopeDatabaseSession::class, GuardImpersonation::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->level(DomainError::class, LogLevel::WARNING);

        $exceptions->report(function (QueryException $exception): void {
            if (str_contains($exception->getMessage(), 'AUDIT_APPEND_ONLY')) {
                Log::critical('Attempt to modify the audit trail.', ['sql' => $exception->getSql()]);
            }
        });

        // API error contract of todo/todo.md §137; HTML pages show the reference to quote to support.
        $exceptions->render(function (DomainError $error, Request $request): Response {
            $reference = RequestContext::correlationId();

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'code' => $error->errorCode(),
                    'message' => $error->getMessage(),
                    'detail' => null,
                    'correlation_id' => $reference,
                    'retryable' => $error->retryable(),
                    'field_errors' => [],
                ], $error->status());
            }

            return response()->view('errors.domain', [
                'message' => $error->getMessage(),
                'retryable' => $error->retryable(),
                'reference' => $reference,
            ], $error->status());
        });
    })->create();
