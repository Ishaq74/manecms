<?php

use Illuminate\Support\Arr;

/**
 * @return array<string, true>
 */
function translationKeysUsedInSource(string $projectRoot): array
{
    $pattern = '/(?:__|trans|@lang)\(\s*(?:\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\]|\\\\.)*)")/u';
    $keys = [];

    foreach (['app', 'resources/views', 'routes'] as $directory) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator("{$projectRoot}/{$directory}", FilesystemIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            // The landing page is still authored in French; it moves to translation keys in P07.
            if (! str_ends_with((string) $file->getFilename(), '.php') || $file->getFilename() === 'home.blade.php') {
                continue;
            }

            preg_match_all($pattern, (string) file_get_contents($file->getPathname()), $matches);

            foreach (array_keys($matches[0]) as $index) {
                $key = $matches[1][$index] !== '' ? $matches[1][$index] : $matches[2][$index];
                $keys[stripslashes($key)] = true;
            }
        }
    }

    return $keys;
}

it('translates every interface string into French', function (): void {
    $projectRoot = dirname(__DIR__, 2);

    /** @var array<string, string> $french */
    $french = json_decode((string) file_get_contents("{$projectRoot}/lang/fr.json"), true, flags: JSON_THROW_ON_ERROR);

    $missing = array_keys(array_diff_key(translationKeysUsedInSource($projectRoot), $french));

    expect($missing)->toBe([]);
});

it('mirrors every framework translation key in French', function (string $file): void {
    $projectRoot = dirname(__DIR__, 2);

    $english = Arr::dot(require "{$projectRoot}/vendor/laravel/framework/src/Illuminate/Translation/lang/en/{$file}.php");
    $french = Arr::dot(require "{$projectRoot}/lang/fr/{$file}.php");

    $english = array_filter($english, fn (string $key): bool => ! str_starts_with($key, 'custom') && ! str_starts_with($key, 'attributes'), ARRAY_FILTER_USE_KEY);

    expect(array_keys(array_diff_key($english, $french)))->toBe([]);
})->with(['auth', 'pagination', 'passwords', 'validation']);
