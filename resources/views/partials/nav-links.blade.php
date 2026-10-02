@php
    $links = $links ?? [
        ['route' => 'home', 'label' => __('Home')],
        ...(auth()->check() ? [['route' => 'dashboard', 'label' => __('Dashboard')]] : []),
    ];
@endphp

@foreach ($links as $link)
    <x-mane::nav-link
        :href="route($link['route'])"
        :current="request()->routeIs($link['route'])"
        x-on:click="$tsui.close.slide('mobile-navigation')"
        wire:key="nav-{{ $link['route'] }}"
    >
        {{ $link['label'] }}
    </x-mane::nav-link>
@endforeach
