@php
    $links = $links ?? [
        ['route' => 'home', 'label' => __('Home')],
        ...(auth()->check() ? [['route' => 'dashboard', 'label' => __('Dashboard')]] : []),
    ];
@endphp

@foreach ($links as $link)
    @php $isCurrent = request()->routeIs($link['route']); @endphp

    <x-link
        navigate
        colorless
        href="{{ route($link['route']) }}"
        x-on:click="$tsui.close.slide('mobile-navigation')"
        @class([
            'rounded-lg px-3 py-2 text-sm transition-colors no-underline',
            'bg-dark-800/5 font-medium text-dark-900 dark:bg-white/10 dark:text-white' => $isCurrent,
            'text-dark-600 hover:bg-dark-800/5 hover:text-dark-900 dark:text-dark-300 dark:hover:bg-white/[7%] dark:hover:text-white' => ! $isCurrent,
        ])
        :aria-current="$isCurrent ? 'page' : null"
    >
        {{ $link['label'] }}
    </x-link>
@endforeach