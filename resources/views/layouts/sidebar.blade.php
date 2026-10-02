{{-- Authenticated app pages with the collapsible sidebar: workspaces and settings. --}}
@php($tenantContext = app(\App\Domain\Tenancy\Context\TenantContext::class))
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
                        <div class="flex flex-col gap-2 px-4 pb-2">
                            <div class="flex h-16 shrink-0 items-center">
                                <x-app-logo :href="route('home')" wire:navigate />
                            </div>

                            <x-workspace-switcher />
                        </div>
                    </x-slot:brand>

                    <x-slot:brandCollapsed>
                        <div class="flex h-16 shrink-0 items-center px-4">
                            <x-app-logo :show-name="false" :href="route('home')" wire:navigate />
                        </div>
                    </x-slot:brandCollapsed>


                    @if ($tenantContext->isInstalled())
                        @can('update', $tenantContext->workspace())
                            <x-mane::sidebar.item
                                icon="cog-6-tooth"
                                :href="route('workspace.settings', $tenantContext->workspace())"
                                :current="request()->routeIs('workspace.settings')"
                                :text="__('Workspace settings')"
                            />
                        @endcan

                        @can('tenancy.member.view')
                            <x-mane::sidebar.item
                                icon="users"
                                :href="route('members.index', $tenantContext->workspace())"
                                :current="request()->routeIs('members.index')"
                                :text="__('Members')"
                            />
                        @endcan

                        @can('tenancy.role.manage')
                            <x-mane::sidebar.item
                                icon="key"
                                :href="route('roles.index', $tenantContext->workspace())"
                                :current="request()->routeIs('roles.index')"
                                :text="__('Roles')"
                            />
                        @endcan

                        @can('viewAny', \App\Domain\Audit\Models\AuditEvent::class)
                            <x-mane::sidebar.item
                                icon="shield-check"
                                :href="route('audit.index', $tenantContext->workspace())"
                                :current="request()->routeIs('audit.index')"
                                :text="__('Audit')"
                            />
                        @endcan

                        @can('update', $tenantContext->tenant())
                            <x-mane::sidebar.item
                                icon="building-office"
                                :href="route('tenant.settings', $tenantContext->workspace())"
                                :current="request()->routeIs('tenant.settings')"
                                :text="__('Space settings')"
                            />
                        @endcan
                    @endif

                    <x-mane::sidebar.separator :text="__('Platform')" />

                    <x-mane::sidebar.item
                        icon="eye"
                        :href="route('home')"
                        :current="request()->routeIs('home')"
                        :text="__('View site')"
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