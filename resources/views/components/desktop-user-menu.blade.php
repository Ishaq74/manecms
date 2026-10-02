@props([
    'testPrefix' => 'desktop-user-menu',
    'placement' => 'bottom',
])

<div {{ $attributes->class('w-full') }}>
    <x-mane::dropdown :placement="$placement" align="start" :label="__('Account')">
        <x-slot:trigger>
            <x-mane::dropdown.trigger data-test="{{ $testPrefix }}-button">
                <x-mane::avatar :text="auth()->user()->initials()" size="sm" />

                <span class="grid flex-1 text-start text-sm leading-tight">
                    <span class="truncate font-medium text-fg">{{ auth()->user()->name }}</span>
                </span>

                <x-mane::icon name="chevron-up-down" class="size-4 text-fg-muted" />
            </x-mane::dropdown.trigger>
        </x-slot:trigger>

        <x-slot:header>
            <div class="flex items-center gap-2 text-start text-sm">
                <x-mane::avatar :text="auth()->user()->initials()" />

                <span class="grid flex-1 text-start text-sm leading-tight">
                    <span class="truncate font-medium text-fg">{{ auth()->user()->name }}</span>
                    <span class="truncate text-fg-muted">{{ auth()->user()->email }}</span>
                </span>
            </div>
        </x-slot:header>

        <x-mane::dropdown.item navigate icon="squares-2x2" :href="route('dashboard')" :text="__('Dashboard')" />
        <x-mane::dropdown.item navigate icon="user-circle" :href="route('profile.edit')" :text="__('Profile')" />

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <x-mane::dropdown.item
                separator
                type="submit"
                icon="arrow-uturn-left"
                data-test="{{ $testPrefix }}-logout"
                :text="__('Log out')"
            />
        </form>
    </x-mane::dropdown>
</div>
