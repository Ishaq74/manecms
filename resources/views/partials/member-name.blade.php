{{-- Name cell of the members table. --}}
<div class="flex items-center gap-3">
    <x-mane::avatar :text="$member->user->initials()" size="sm" />

    <div class="flex min-w-0 flex-col">
        <span class="truncate font-medium text-fg">
            {{ $member->user->name }}

            @if ($isCurrentUser)
                <span class="text-fg-muted">({{ __('you') }})</span>
            @endif
        </span>

        @if ($member->is_owner)
            <span class="text-xs text-fg-muted">{{ __('Owner of the space') }}</span>
        @endif
    </div>
</div>
