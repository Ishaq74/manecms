<?php

/**
 * @return list<string>
 */
function filesReferencingFlux(string $projectRoot): array
{
    $offenders = [];

    foreach (['app', 'config', 'resources', 'routes', 'bootstrap/app.php', 'bootstrap/providers.php'] as $path) {
        $absolutePath = "{$projectRoot}/{$path}";
        $files = is_file($absolutePath)
            ? [new SplFileInfo($absolutePath)]
            : new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absolutePath, FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if (preg_match('/<flux:|\bFlux\\\\|livewire\/flux/i', (string) file_get_contents($file->getPathname())) === 1) {
                $offenders[] = $file->getPathname();
            }
        }
    }

    return $offenders;
}

it('does not install Flux', function (): void {
    $projectRoot = dirname(__DIR__, 2);

    /** @var array{packages: list<array{name: string}>, packages-dev: list<array{name: string}>} $lock */
    $lock = json_decode((string) file_get_contents("{$projectRoot}/composer.lock"), true, flags: JSON_THROW_ON_ERROR);
    /** @var array{require: array<string, string>, require-dev: array<string, string>} $manifest */
    $manifest = json_decode((string) file_get_contents("{$projectRoot}/composer.json"), true, flags: JSON_THROW_ON_ERROR);

    $packages = [
        ...array_column($lock['packages'], 'name'),
        ...array_column($lock['packages-dev'], 'name'),
        ...array_keys($manifest['require']),
        ...array_keys($manifest['require-dev']),
    ];

    expect($packages)->not->toContain('livewire/flux')
        ->and((string) file_get_contents("{$projectRoot}/package.json"))->not->toContain('flux');
});

it('does not reference Flux in the application sources', function (): void {
    expect(filesReferencingFlux(dirname(__DIR__, 2)))->toBe([]);
});
