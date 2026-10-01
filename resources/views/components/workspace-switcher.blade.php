<div {{ $attributes->class('w-full') }}>
    <x-dropdown position="bottom-start" width="md">
        <x-slot:action>
            <button
                type="button"
                x-on:click="show = !show"
                data-test="workspace-switcher-button"
                aria-label="{{ __('Workspaces') }}"
                class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-start text-sm transition hover:bg-dark-800/5 dark:hover:bg-white/10"
            >
                <x-icon name="building-office-2" sm class="text-dark-500" />

                <span class="flex-1 truncate font-medium text-dark-800 dark:text-white">
                    {{ $current?->name ?? __('Choose a workspace') }}
                </span>

                <x-icon name="chevron-up-down" sm class="text-dark-500" />
            </button>
        </x-slot:action>

        @foreach ($tenants as $tenant)
            <div class="px-3 pb-1 pt-2 text-xs font-medium uppercase tracking-wide text-dark-500 dark:text-dark-400">
                {{ $tenant->name }}
            </div>

            @foreach ($tenant->activeWorkspaces as $workspace)
                <x-dropdown.items
                    navigate
                    :icon="$current?->is($workspace) ? 'check' : 'squares-2x2'"
                    :href="route('workspace.home', $workspace)"
                    :text="$workspace->name"
                />
            @endforeach
        @endforeach

        @if ($canCreateWorkspace)
            <x-dropdown.items
                separator
                navigate
                icon="plus"
                :href="route('workspace.create', $current)"
                :text="__('Create a workspace')"
            />
        @endif

        <x-dropdown.items
            :separator="! $canCreateWorkspace"
            navigate
            icon="building-office"
            :href="route('onboarding')"
            :text="__('Create a new space')"
        />
    </x-dropdown>
</div>
