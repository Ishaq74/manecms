<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

{{--
    Owns the dark theme class. Three jobs:

    1. apply the persisted mode before the first paint, otherwise the page
       renders light then flips once Alpine boots (FOUC)
    2. apply every later change, driven by the `theme` event the switch
       dispatches. The event does not bubble, so it is caught in the capture
       phase, which still reaches the target
    3. follow changes made in another tab

    The class deliberately lives here and nowhere else: binding it with Alpine
    on <html> puts it at the mercy of that element being morphed, and a lost
    binding makes the switch look dead until the next reload.

    Mirrors tallstackui_darkTheme, which stores the mode under "dark-theme" and
    is what x-theme-switch writes to.
--}}
<script>
    (() => {
        const STORAGE_KEY = 'dark-theme';
        const DEFAULT_MODE = 'dark';

        const read = () => {
            try {
                return localStorage.getItem(STORAGE_KEY);
            } catch (exception) {
                return null;
            }
        };

        const resolve = (mode) => {
            const resolved = ['light', 'dark', 'system'].includes(mode) ? mode : DEFAULT_MODE;

            return resolved === 'system'
                ? window.matchMedia('(prefers-color-scheme: dark)').matches
                : resolved === 'dark';
        };

        const apply = (mode) => document.documentElement.classList.toggle('dark', resolve(mode));

        const stored = read() ?? DEFAULT_MODE;
        apply(stored);

        document.addEventListener('theme', (event) => {
            const { mode, darkTheme } = event.detail ?? {};

            if (typeof darkTheme === 'boolean') {
                document.documentElement.classList.toggle('dark', darkTheme);
            }

            if (mode) {
                apply(mode);
            }
        }, true);

        window.addEventListener('storage', (event) => {
            if (event.key === STORAGE_KEY) {
                apply(event.newValue ?? DEFAULT_MODE);
            }
        });

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if ((read() ?? DEFAULT_MODE) === 'system') {
                apply('system');
            }
        });
    })();
</script>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@fonts

<tallstackui:script />

@livewireStyles

@vite(['resources/css/app.css', 'resources/js/app.js'])