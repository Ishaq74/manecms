<div class="flex items-start max-md:flex-col">
    <nav aria-label="{{ __('Settings') }}" class="me-10 w-full pb-4 md:w-[220px]">
        <ul class="flex flex-col gap-1">
            @foreach ([
                'profile.edit' => __('Profile'),
                'security.edit' => __('Security'),
                'appearance.edit' => __('Appearance'),
            ] as $routeName => $label)
                @php $isCurrent = request()->routeIs($routeName); @endphp

                <li>
                    <a
                        href="{{ route($routeName) }}"
                        wire:navigate
                        @class([
                            'flex items-center rounded-lg px-3 py-2 text-sm transition-colors',
                            'bg-zinc-800/5 text-zinc-900 dark:bg-white/10 dark:text-white' => $isCurrent,
                            'text-zinc-500 hover:bg-zinc-800/5 hover:text-zinc-800 dark:text-zinc-400 dark:hover:bg-white/[7%] dark:hover:text-white' => ! $isCurrent,
                        ])
                        @if ($isCurrent) aria-current="page" @endif
                    >
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <hr class="border-zinc-200 md:hidden dark:border-zinc-700" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <h2 class="text-lg font-medium tracking-tight text-zinc-800 dark:text-white">
            {{ $heading ?? '' }}
        </h2>

        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ $subheading ?? '' }}
        </p>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
