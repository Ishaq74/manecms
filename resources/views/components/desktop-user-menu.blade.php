@props([
    'testPrefix' => 'desktop-user-menu',
])

<div {{ $attributes->class('w-full') }}>
    <x-dropdown position="bottom-start" width="md">
        <x-slot:action>
            <button
                type="button"
                x-on:click="show = !show"
                data-test="{{ $testPrefix }}-button"
                class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-start text-sm transition hover:bg-dark-800/5 dark:hover:bg-white/10"
            >
                <x-avatar
                    :text="auth()->user()->initials()"
                    sm
                />

                <span class="grid flex-1 text-start text-sm leading-tight">
                    <span class="truncate font-medium text-dark-800 dark:text-white">
                        {{ auth()->user()->name }}
                    </span>
                </span>

                <x-icon
                    name="chevron-up-down"
                    sm
                    class="text-dark-500"
                />
            </button>
        </x-slot:action>

        <x-slot:header>
            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                <x-avatar
                    :text="auth()->user()->initials()"
                />

                <span class="grid flex-1 text-start text-sm leading-tight">
                    <span class="truncate font-medium text-dark-800 dark:text-white">
                        {{ auth()->user()->name }}
                    </span>

                    <span class="truncate text-dark-500 dark:text-dark-400">
                        {{ auth()->user()->email }}
                    </span>
                </span>
            </div>
        </x-slot:header>

        <x-dropdown.items
            navigate
            icon="squares-2x2"
            :href="route('dashboard')"
            :text="__('Dashboard')"
        />

        <x-dropdown.items
            navigate
            icon="user-circle"
            :href="route('profile.edit')"
            :text="__('Profile')"
        />

        <form
            method="POST"
            action="{{ route('logout') }}"
        >
            @csrf

            <x-dropdown.items
                separator
                type="submit"
                icon="arrow-uturn-left"
                data-test="{{ $testPrefix }}-logout"
                :text="__('Log out')"
            />
        </form>
    </x-dropdown>
</div>