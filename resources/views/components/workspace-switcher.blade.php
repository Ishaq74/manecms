<div {{ $attributes->class('w-full') }}>
    <x-mane::dropdown align="start" :label="__('Workspaces')">
        <x-slot:trigger>
            <x-mane::dropdown.trigger data-test="workspace-switcher-button" aria-label="{{ __('Workspaces') }}">
                <x-mane::icon name="building-office-2" class="size-4 text-fg-muted" />

                <span class="flex-1 truncate font-medium text-fg">
                    {{ $current?->name ?? __('Choose a workspace') }}
                </span>

                <x-mane::icon name="chevron-up-down" class="size-4 text-fg-muted" />
            </x-mane::dropdown.trigger>
        </x-slot:trigger>

        @foreach ($tenants as $tenant)
            <x-mane::dropdown.group :label="$tenant->name" wire:key="tenant-{{ $tenant->id }}">
            @foreach ($tenant->activeWorkspaces as $workspace)
                <x-mane::dropdown.item
                    navigate
                    :icon="$current?->is($workspace) ? 'check' : 'squares-2x2'"
                    :href="route('workspace.home', $workspace)"
                    :text="$workspace->name"
                        :aria-current="$current?->is($workspace) ? 'true' : null"
                />
            @endforeach
            </x-mane::dropdown.group>
        @endforeach

        @if ($canCreateWorkspace)
            <x-mane::dropdown.item
                separator
                navigate
                icon="plus"
                :href="route('workspace.create', $current)"
                :text="__('Create a workspace')"
            />
        @endif

        <x-mane::dropdown.item
            :separator="! $canCreateWorkspace"
            navigate
            icon="building-office"
            :href="route('onboarding')"
            :text="__('Create a new space')"
        />
    </x-mane::dropdown>
</div>
