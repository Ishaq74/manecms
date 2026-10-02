{{--
    Light, dark or system (§468); the mode is applied by partials/head.
    TallStackUI renders the three choices as icon-only buttons: ManeUI names them
    and exposes the selected one with aria-pressed.
--}}
<div
    x-data="{ labels: @js(['light' => __('Light'), 'dark' => __('Dark'), 'system' => __('System')]) }"
    x-init="$nextTick(() => $el.querySelectorAll('button[x-on\\:click^=setAs]').forEach((button) => {
        const mode = button.getAttribute('x-on:click').match(/setAs\('(\w+)'\)/)?.[1];
        if (mode) { button.setAttribute('aria-label', labels[mode]); button.setAttribute('title', labels[mode]); }
    }))"
>
    <x-ts-theme-switch {{ $attributes }} block />
</div>
