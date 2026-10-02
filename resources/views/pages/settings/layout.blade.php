{{-- Account settings frame: page header, section navigation and the current section. --}}
@props([
    'heading',
    'subheading' => null,
])

<div class="flex w-full flex-col gap-8">
    <x-mane::page-header :title="__('Settings')" :description="__('Manage your profile and account settings')" />

    <div class="flex items-start gap-10 max-md:flex-col max-md:gap-6">
        <nav aria-label="{{ __('Settings') }}" class="w-full md:w-56 md:shrink-0">
            <ul class="flex flex-col gap-1 max-md:flex-row max-md:overflow-x-auto">
                @foreach ([
                    'profile.edit' => [__('Profile'), 'user-circle'],
                    'security.edit' => [__('Security'), 'shield-check'],
                    'appearance.edit' => [__('Appearance'), 'swatch'],
                ] as $routeName => [$label, $icon])
                    <li wire:key="settings-nav-{{ $routeName }}">
                        <x-mane::nav-link :href="route($routeName)" :icon="$icon" :current="request()->routeIs($routeName)">
                            {{ $label }}
                        </x-mane::nav-link>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="flex w-full min-w-0 flex-1 flex-col gap-6">
            <x-mane::section-header :title="$heading" :description="$subheading" />

            {{ $slot }}
        </div>
    </div>
</div>
