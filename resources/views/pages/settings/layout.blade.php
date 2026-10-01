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
                    <x-link
                        navigate
                        colorless
                        href="{{ route($routeName) }}"
                        @class([
                            'flex items-center rounded-lg px-3 py-2 text-sm transition-colors no-underline',
                            'bg-dark-800/5 text-dark-900 dark:bg-white/10 dark:text-white' => $isCurrent,
                            'text-dark-600 hover:bg-dark-800/5 hover:text-dark-900 dark:text-dark-300 dark:hover:bg-white/[7%] dark:hover:text-white' => ! $isCurrent,
                        ])
                        :aria-current="$isCurrent ? 'page' : null"
                    >
                        {{ $label }}
                    </x-link>
                </li>
            @endforeach
        </ul>
    </nav>

    <hr class="border-dark-200 md:hidden dark:border-dark-700" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <h2 class="text-lg font-medium tracking-tight text-dark-800 dark:text-white">
            {{ $heading ?? '' }}
        </h2>

        <p class="text-sm text-dark-500 dark:text-dark-400">
            {{ $subheading ?? '' }}
        </p>

        <div class="mt-5 w-full">
            {{ $slot }}
        </div>
    </div>
</div>
