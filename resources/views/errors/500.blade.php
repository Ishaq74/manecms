{{-- Standalone on purpose: it must render even when the failure comes from the database or the session. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ App\Enums\TextDirection::current()->value }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('Something went wrong on our side') }}</title>
        <style>body{font-family:system-ui,sans-serif;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0;color:#1f2937}main{max-width:28rem;padding:1rem;text-align:center}</style>
    </head>
    <body class="flex min-h-svh items-center justify-center bg-white px-4 text-dark-800 antialiased dark:bg-dark-900 dark:text-dark-100">
        <main class="flex max-w-md flex-col gap-4 text-center" data-test="server-error">
            <h1 class="text-xl font-semibold tracking-tight">{{ __('Something went wrong on our side') }}</h1>

            <p class="text-sm text-dark-600 dark:text-dark-300">
                {{ __('The error has been recorded. You can try again in a moment.') }}
            </p>

            <p class="text-xs text-dark-500 dark:text-dark-400">
                {{ __('Reference: :reference', ['reference' => \App\Domain\Platform\Observability\RequestContext::correlationId()]) }}
            </p>

            <p>
                <a href="{{ url('/') }}" class="text-sm underline">{{ __('Home') }}</a>
            </p>
        </main>
    </body>
</html>
