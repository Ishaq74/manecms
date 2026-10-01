<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

{{--
    Applies the persisted theme before the first paint.

    The `dark` variant is scoped to `.dark`, and the class is bound by Alpine at
    runtime. Alpine only boots after the document has been parsed, so without
    this the page would paint in the light theme and then flip.

    Mirrors the resolution of the tallstackui_darkTheme helper, which stores the
    mode under the same "dark-theme" key and defaults to dark here.
--}}
<script>
    (() => {
        const mode = localStorage.getItem('dark-theme') ?? 'dark';
        const resolved = ['light', 'dark', 'system'].includes(mode) ? mode : 'dark';
        const dark = resolved === 'dark'
            || (resolved !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);

        document.documentElement.classList.toggle('dark', dark);
    })();
</script>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@fonts

<tallstackui:script />

@livewireStyles

@vite(['resources/css/app.css', 'resources/js/app.js'])