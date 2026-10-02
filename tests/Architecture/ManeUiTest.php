<?php

/**
 * Blade views of the application, ManeUI excluded, as [relative path => contents].
 *
 * @return array<string, string>
 */
function applicationViews(bool $includeManeUi = false): array
{
    $root = dirname(__DIR__, 2);
    $views = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/resources/views", FilesystemIterator::SKIP_DOTS)) as $file) {
        $relative = str_replace('\\', '/', substr((string) $file->getPathname(), strlen($root) + 1));

        if (! str_ends_with($relative, '.blade.php') || (! $includeManeUi && str_starts_with($relative, 'resources/views/mane/'))) {
            continue;
        }

        $views[$relative] = (string) file_get_contents($file->getPathname());
    }

    return $views;
}

/**
 * ManeUI component names as used in tags, e.g. "button" or "dropdown.item".
 *
 * @return list<string>
 */
function maneUiComponents(): array
{
    $root = dirname(__DIR__, 2).'/resources/views/mane/';
    $components = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
        $relative = str_replace('\\', '/', substr((string) $file->getPathname(), strlen($root)));

        // Livewire views of ManeUI (the DataTable) are included, not used as tags.
        if (str_starts_with($relative, 'livewire/')) {
            continue;
        }

        $components[] = str_replace(['/index.blade.php', '.blade.php', '/'], ['', '', '.'], $relative);
    }

    sort($components);

    return $components;
}

it('keeps TallStackUI behind ManeUI', function (): void {
    $offenders = array_keys(array_filter(applicationViews(), fn (string $view): bool => preg_match('/<x-ts-/', $view) === 1));

    expect($offenders)->toBe([]);
});

it('keeps raw form controls inside ManeUI', function (): void {
    $offenders = array_keys(array_filter(
        applicationViews(),
        fn (string $view): bool => preg_match('/<(button|select|textarea)\b|<input\b(?![^>]*type="hidden")/', $view) === 1,
    ));

    expect($offenders)->toBe([]);
});

it('only uses logical direction utilities', function (): void {
    $physical = '/(?<![\w-])-?(?:ml|mr|pl|pr|left|right|rounded-[lr]|rounded-[tb][lr]|border-[lr]|scroll-m[lr])-[\w\[]|(?<![\w-])(?:text-(?:left|right)|border-[lr])(?![\w-])/';
    $sources = applicationViews(includeManeUi: true) + [
        'app/Providers/TallStackUiServiceProvider.php' => (string) file_get_contents(dirname(__DIR__, 2).'/app/Providers/TallStackUiServiceProvider.php'),
    ];

    $offenders = array_keys(array_filter($sources, fn (string $source): bool => preg_match($physical, $source) === 1));

    expect($offenders)->toBe([]);
});

it('keeps every view inside the three palette ramps', function (): void {
    $offPalette = '/\b(?:gray|slate|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose)-\d/';

    $offenders = array_keys(array_filter(applicationViews(includeManeUi: true), fn (string $view): bool => preg_match($offPalette, $view) === 1));

    expect($offenders)->toBe([]);
});

it('colours application views with semantic tokens only', function (): void {
    $rawRamp = '/(?<![\w-])(?:[a-z-]+:)*(?:text|bg|border|ring|divide|placeholder|stroke|fill|from|via|to)-(?:dark|primary|secondary|white|black)(?:-\d+)?(?:\/[\w\[\]%.]+)?(?![\w-])/';
    $allowed = [
        // Standalone page rendered without Vite when the application itself is failing.
        'resources/views/errors/500.blade.php' => true,
    ];

    $offenders = [];

    foreach (applicationViews() as $path => $view) {
        // A QR code stays black on white whatever the theme, or authenticator apps cannot read it.
        $view = str_replace('bg-white p-3 rounded dark:invert dark:brightness-150', '', $view);

        if (! isset($allowed[$path]) && preg_match_all($rawRamp, $view, $matches) > 0) {
            $offenders[$path] = array_values(array_unique($matches[0]));
        }
    }

    expect($offenders)->toBe([]);
});

it('titles every application page with the ManeUI page header', function (): void {
    $editorial = ['resources/views/home.blade.php', 'resources/views/errors/500.blade.php'];

    $offenders = array_keys(array_filter(
        applicationViews(),
        fn (string $view, string $path): bool => ! in_array($path, $editorial, true) && preg_match('/<h1\b/', $view) === 1,
        ARRAY_FILTER_USE_BOTH,
    ));

    expect($offenders)->toBe([]);
});

it('documents every ManeUI component', function (): void {
    $documentation = (string) file_get_contents(dirname(__DIR__, 2).'/docs/design-system/components.md');

    $undocumented = array_values(array_filter(maneUiComponents(), fn (string $component): bool => ! str_contains($documentation, "`x-mane::{$component}`")));

    expect($undocumented)->toBe([]);
});

it('shows every ManeUI component in the catalogue', function (): void {
    $root = dirname(__DIR__, 2);
    $catalogue = (string) file_get_contents("{$root}/resources/views/pages/mane/⚡catalog.blade.php");

    // Frame components render the application shell itself; the catalogue sits inside them.
    $frame = ['dropdown.trigger', 'layout', 'layout.header', 'sidebar', 'sidebar.item', 'sidebar.separator', 'theme-toggle', 'toast'];

    $missing = array_values(array_filter(
        array_diff(maneUiComponents(), $frame),
        fn (string $component): bool => ! str_contains($catalogue, "<x-mane::{$component}"),
    ));

    expect($missing)->toBe([]);
});
