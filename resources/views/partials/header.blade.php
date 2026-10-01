@props([
    'title',
])

<header class="sticky top-0 z-30 border-b border-zinc-200 bg-white/80 backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/80">
    <div class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
        <x-app-logo :href="route('home')" navigate />

        <nav aria-label="{{ __('Main navigation') }}" class="flex items-center gap-1">
            <a
                href="{{ route('home') }}"
                wire:navigate
                @class([
                    'rounded-lg px-3 py-2 text-sm transition-colors',
                    'bg-zinc-800/5 font-medium text-zinc-900 dark:bg-white/10 dark:text-white' => request()->routeIs('home'),
                    'text-zinc-500 hover:bg-zinc-800/5 hover:text-zinc-800 dark:text-zinc-400 dark:hover:bg-white/[7%] dark:hover:text-white' => ! request()->routeIs('home'),
                ])
                @if (request()->routeIs('home')) aria-current="page" @endif
            >
                {{ __('Home') }}
            </a>

            @auth
                <a
                    href="{{ route('dashboard') }}"
                    wire:navigate
                    @class([
                        'rounded-lg px-3 py-2 text-sm transition-colors',
                        'bg-zinc-800/5 font-medium text-zinc-900 dark:bg-white/10 dark:text-white' => request()->routeIs('dashboard'),
                        'text-zinc-500 hover:bg-zinc-800/5 hover:text-zinc-800 dark:text-zinc-400 dark:hover:bg-white/[7%] dark:hover:text-white' => ! request()->routeIs('dashboard'),
                    ])
                    @if (request()->routeIs('dashboard')) aria-current="page" @endif
                >
                    {{ __('Dashboard') }}
                </a>
            @endauth
        </nav>

        <div class="ms-auto flex items-center gap-2">
            <x-theme-switch simple only-icons />

            @auth
                <x-desktop-user-menu />
            @else
                <x-button flat sm :href="route('login')" navigate :text="__('Log in')" />
                <x-button sm :href="route('register')" navigate :text="__('Register')" />
            @endauth
        </div>
    </div>
</header>