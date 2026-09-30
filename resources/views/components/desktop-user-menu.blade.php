<div {{ $attributes->class('w-full') }}>
    <x-dropdown position="bottom-start" width="md">
    <x-slot:action>
        <button
            type="button"
            data-test="sidebar-menu-button"
            class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-start text-sm hover:bg-zinc-800/5 dark:hover:bg-white/10"
        >
            <x-avatar :text="auth()->user()->initials()" sm />

            <span class="grid flex-1 text-start text-sm leading-tight">
                <span class="truncate font-medium text-zinc-800 dark:text-white">
                    {{ auth()->user()->name }}
                </span>
            </span>

            <x-icon name="chevron-up-down" sm class="text-zinc-500" />
        </button>
    </x-slot:action>

    <x-slot:header>
        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
            <x-avatar :text="auth()->user()->initials()" />

            <span class="grid flex-1 text-start text-sm leading-tight">
                <span class="truncate font-medium text-zinc-800 dark:text-white">
                    {{ auth()->user()->name }}
                </span>
                <span class="truncate text-zinc-500 dark:text-zinc-400">
                    {{ auth()->user()->email }}
                </span>
            </span>
        </div>
    </x-slot:header>

    <x-dropdown.items
        :href="route('profile.edit')"
        icon="clipboard-document"
        navigate
        :text="__('Settings')"
    />

    <x-dropdown.items separator icon="arrow-uturn-left">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                data-test="logout-button"
                class="w-full cursor-pointer text-start text-inherit"
            >
                {{ __('Log out') }}
            </button>
        </form>
    </x-dropdown.items>
    </x-dropdown>
</div>