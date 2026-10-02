{{-- Operator back-office: separate from every workspace, no tenant context. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ App\Enums\TextDirection::current()->value }}"
      x-data="tallstackui_darkTheme({ default: 'dark' })">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-svh bg-surface text-fg antialiased">
        <x-mane::layout>
            <x-slot:menu>
                <x-mane::sidebar>
                    <x-slot:brand>
                        <div class="flex h-16 shrink-0 items-center gap-2 px-4">
                            <x-app-logo :href="route('platform.dashboard')" wire:navigate />
                            <x-mane::badge variant="secondary" size="sm" :text="__('Platform')" />
                        </div>
                    </x-slot:brand>

                    <x-slot:brandCollapsed>
                        <div class="flex h-16 shrink-0 items-center px-4">
                            <x-app-logo :show-name="false" :href="route('platform.dashboard')" wire:navigate />
                        </div>
                    </x-slot:brandCollapsed>

                    <x-mane::sidebar.item
                        icon="heart"
                        :href="route('platform.dashboard')"
                        :current="request()->routeIs('platform.dashboard')"
                        :text="__('Health')"
                    />

                    <x-mane::sidebar.item
                        icon="building-office-2"
                        :href="route('platform.tenants.index')"
                        :current="request()->routeIs('platform.tenants.*')"
                        :text="__('Tenants')"
                    />

                    <x-mane::sidebar.item
                        icon="shield-check"
                        :href="route('platform.audit')"
                        :current="request()->routeIs('platform.audit')"
                        :text="__('Platform audit')"
                    />

                    <x-mane::sidebar.separator :text="__('Application')" />

                    <x-mane::sidebar.item
                        icon="arrow-uturn-left"
                        :href="route('dashboard')"
                        :text="__('Back to my workspaces')"
                    />

                    <x-slot:footer>
                        <x-desktop-user-menu placement="top" />
                    </x-slot:footer>
                </x-mane::sidebar>
            </x-slot:menu>

            <x-slot:header>
                <x-mane::layout.header>
                    <x-slot:right>
                        <x-mane::theme-toggle />
                    </x-slot:right>
                </x-mane::layout.header>
            </x-slot:header>

            <div class="w-full">
                {{ $slot }}
            </div>

            <x-slot:footer>
                @include('partials.footer')
            </x-slot:footer>
        </x-mane::layout>

        <x-mane::toast />

        @livewireScripts
    </body>
</html>
