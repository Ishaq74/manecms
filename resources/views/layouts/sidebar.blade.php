{{-- Authenticated app pages with the collapsible sidebar: workspaces and settings. --}}
@php($tenantContext = app(\App\Domain\Tenancy\Context\TenantContext::class))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="tallstackui_darkTheme({ default: 'dark' })">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-svh bg-white text-dark-800 antialiased dark:bg-dark-900 dark:text-dark-100">
        <x-layout>
            <x-slot:menu>
                <x-side-bar collapsible smart navigate thin-scroll>
                    <x-slot:brand>
                        <div class="flex h-16 shrink-0 items-center px-4">
                            <x-app-logo :href="route('home')" wire:navigate />
                        </div>
                    </x-slot:brand>

                    <x-slot:brandCollapsed>
                        <div class="flex h-16 shrink-0 items-center px-4">
                            <x-app-logo :show-name="false" :href="route('home')" wire:navigate />
                        </div>
                    </x-slot:brandCollapsed>

                    <div class="px-2 pb-2">
                        <x-workspace-switcher />
                    </div>

                    @if ($tenantContext->isInstalled())
                        @can('update', $tenantContext->workspace())
                            <x-side-bar.item
                                icon="cog-6-tooth"
                                :href="route('workspace.settings', $tenantContext->workspace())"
                                :current="request()->routeIs('workspace.settings')"
                                :text="__('Workspace settings')"
                            />
                        @endcan

                        @can('viewAny', \App\Domain\Audit\Models\AuditEvent::class)
                            <x-side-bar.item
                                icon="shield-check"
                                :href="route('audit.index', $tenantContext->workspace())"
                                :current="request()->routeIs('audit.index')"
                                :text="__('Audit')"
                            />
                        @endcan

                        @can('update', $tenantContext->tenant())
                            <x-side-bar.item
                                icon="building-office"
                                :href="route('tenant.settings', $tenantContext->workspace())"
                                :current="request()->routeIs('tenant.settings')"
                                :text="__('Space settings')"
                            />
                        @endcan
                    @endif

                    <x-side-bar.separator line :text="__('Platform')" />

                    <x-side-bar.item
                        icon="eye"
                        :href="route('home')"
                        :current="request()->routeIs('home')"
                        :text="__('View site')"
                    />

                    <x-slot:footer>
                        <x-desktop-user-menu />
                    </x-slot:footer>
                </x-side-bar>
            </x-slot:menu>

            <x-slot:header>
                <x-layout.header>
                    <x-slot:right>
                        <button
                            type="button"
                            role="switch"
                            aria-label="{{ __('Toggle theme') }}"
                            x-bind:aria-checked="darkTheme.toString()"
                            x-on:click="mode = darkTheme ? 'light' : 'dark'; $el.dispatchEvent(new CustomEvent('theme', { detail: { darkTheme: mode === 'dark', mode: mode } }))"
                            data-test="theme-switch"
                            class="cursor-pointer rounded-md p-1.5 text-dark-500 transition-colors hover:bg-dark-800/5 hover:text-dark-800 dark:text-dark-400 dark:hover:bg-white/10 dark:hover:text-white"
                        >
                            <span class="block dark:hidden">
                                <x-icon name="sun" />
                            </span>

                            <span class="hidden dark:block">
                                <x-icon name="moon" />
                            </span>
                        </button>
                    </x-slot:right>
                </x-layout.header>
            </x-slot:header>

            <div class="w-full">
                {{ $slot }}
            </div>

            <x-slot:footer>
                @include('partials.footer')
            </x-slot:footer>
        </x-layout>

        <x-toast />

        @livewireScripts
    </body>
</html>