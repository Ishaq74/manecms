<?php

declare(strict_types=1);

namespace ManeCms;

use RuntimeException;

/**
 * Orchestrates every quality gate of the project in the cheapest-first order.
 *
 * Usage:
 *   herd php bin/check.php [options]
 *
 * Options:
 *   --fix            Write changes (Pint and Rector both stop reporting and start writing)
 *   --dirty          Restrict Pint, PHPStan and Pest to files changed according to Git
 *   --full           Also run PHP Insights and Pest type coverage (slow)
 *   --only=<stages>  Comma separated list of stages to run (implies skipping the others)
 *   --skip=<stages>  Comma separated list of stages to skip
 *   --help           Show this help
 */
final class CheckRunner
{
    private const MINIMUM_PHP_VERSION = '8.4.1';

    /** @var array<string, string> */
    private const STAGES = [
        'pint' => 'Laravel Pint — code style',
        'rector' => 'Rector — automated refactoring (dry run)',
        'phpstan' => 'PHPStan + Larastan — static analysis',
        'pest' => 'Pest — Unit and Feature test suites',
        'insights' => 'PHP Insights — code quality score (--full)',
        'type-coverage' => 'Pest — type coverage of arguments (--full)',
    ];

    private const SLOW_STAGES = ['insights', 'type-coverage'];

    /** @var array<string, string|null> */
    private const REQUIRED_BINARIES = [
        'pint' => 'vendor/bin/pint',
        'rector' => 'vendor/bin/rector',
        'phpstan' => 'vendor/bin/phpstan',
        'pest' => 'vendor/bin/pest',
        'insights' => null,
        'type-coverage' => 'vendor/bin/pest',
    ];

    /** @var array<string, string> */
    private const REQUIRED_CONFIGURATION = [
        'rector' => 'rector.php',
        'insights' => 'config/insights.php',
    ];

    /** @var list<string> */
    private array $plannedStages = [];

    public function __construct(private readonly array $options) {}

    public static function make(): self
    {
        $options = [];

        foreach (array_slice($GLOBALS['argv'] ?? [], 1) as $argument) {
            [$name, $value] = array_pad(explode('=', $argument, 2), 2, true);
            $options[trim((string) $name)] = $value;
        }

        return new self($options);
    }

    public function run(): int
    {
        $this->guardAgainstUnsupportedPhpVersion();

        if ($this->wantsHelp()) {
            $this->printHelp();

            return 0;
        }

        $this->plannedStages = $this->stagesToRun();
        $results = [];

        foreach ($this->plannedStages as $stage) {
            $startedAt = microtime(true);
            $this->printStageHeading($stage);
            $this->warnAboutMissingConfiguration($stage);

            $exitCode = $this->execute($stage);

            $results[$stage] = [
                'exit_code' => $exitCode,
                'duration' => microtime(true) - $startedAt,
            ];

            if ($exitCode !== 0) {
                break;
            }
        }

        return $this->printSummary($results);
    }

    /**
     * @return list<string>
     */
    private function stagesToRun(): array
    {
        if (array_key_exists('--only', $this->options)) {
            return $this->parseStageList((string) $this->options['--only']);
        }

        $selected = array_keys(self::STAGES);

        if (! $this->wantsFullRun()) {
            $selected = array_values(array_diff($selected, self::SLOW_STAGES));
        }

        if (array_key_exists('--skip', $this->options)) {
            $selected = array_values(
                array_diff($selected, $this->parseStageList((string) $this->options['--skip'])),
            );
        }

        return $selected;
    }

    /**
     * @return list<string>
     */
    private function parseStageList(string $value): array
    {
        $stages = array_values(array_filter(array_map('trim', explode(',', $value))));
        $unknown = array_diff($stages, array_keys(self::STAGES));

        if ($stages === []) {
            throw new RuntimeException('Expected at least one stage name.');
        }

        if ($unknown !== []) {
            throw new RuntimeException('Unknown stage(s): '.implode(', ', $unknown).'.');
        }

        return $stages;
    }

    /**
     * Rector silently succeeds when no configuration file exists, which makes a
     * green stage meaningless. Surface it instead of letting it pass unnoticed.
     */
    private function warnAboutMissingConfiguration(string $stage): void
    {
        $configuration = self::REQUIRED_CONFIGURATION[$stage] ?? null;

        if ($configuration === null || file_exists($configuration)) {
            return;
        }

        printf(
            'WARNING: %s is missing, this stage will analyse nothing.'.PHP_EOL,
            $configuration,
        );
    }

    /**
     * @return list<string>
     */
    private function commandFor(string $stage): array
    {
        $dirty = array_key_exists('--dirty', $this->options) ? ['--dirty'] : [];

        return match ($stage) {
            'pint' => array_merge(
                [PHP_BINARY, 'vendor/bin/pint', '--parallel'],
                $this->wantsFix() ? [] : ['--test'],
                $dirty,
            ),
            'rector' => array_merge(
                [PHP_BINARY, 'vendor/bin/rector', 'process'],
                $this->wantsFix() ? [] : ['--dry-run'],
            ),
            'phpstan' => array_merge([PHP_BINARY, 'vendor/bin/phpstan', 'analyse', '--no-progress'], $dirty),
            'pest' => array_merge([PHP_BINARY, 'vendor/bin/pest'], $dirty),
            'insights' => [PHP_BINARY, 'artisan', 'insights'],
            'type-coverage' => [
                PHP_BINARY, '-d', 'memory_limit=2G', 'vendor/bin/pest', '--type-coverage',
            ],
            default => throw new RuntimeException("No command defined for stage [{$stage}]."),
        };
    }

    private function execute(string $stage): int
    {
        $this->guardAgainstMissingBinary($stage);

        $command = $this->commandFor($stage);
        $process = proc_open($command, [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes);

        if (! is_resource($process)) {
            throw new RuntimeException('Unable to start: '.implode(' ', $command));
        }

        return proc_close($process);
    }

    private function guardAgainstUnsupportedPhpVersion(): void
    {
        if (version_compare(PHP_VERSION, self::MINIMUM_PHP_VERSION, '>=')) {
            return;
        }

        $minimum = self::MINIMUM_PHP_VERSION;
        $running = PHP_VERSION;

        fwrite(STDERR, <<<TXT
        PHP >= {$minimum} is required (running {$running}).

        Your "php" on PATH is probably too old. Use the PHP version served by Herd:

            herd php bin/check.php

        TXT);

        exit(1);
    }

    private function guardAgainstMissingBinary(string $stage): void
    {
        $binary = self::REQUIRED_BINARIES[$stage] ?? null;

        if ($binary === null || file_exists($binary)) {
            return;
        }

        throw new RuntimeException("Missing [{$binary}]. Run `composer install` first.");
    }

    private function printStageHeading(string $stage): void
    {
        $label = self::STAGES[$stage];

        printf(PHP_EOL.'%s  %s%s'.PHP_EOL, str_repeat('=', 4), $stage, ' — '.$label);
    }

    /**
     * @param  array<string, array{exit_code: int, duration: float}>  $results
     */
    private function printSummary(array $results): int
    {
        $failures = 0;

        printf(PHP_EOL.'%s'.PHP_EOL, str_repeat('=', 60));
        printf('%-16s %-10s %s'.PHP_EOL, 'STAGE', 'RESULT', 'DURATION');
        printf('%s'.PHP_EOL, str_repeat('-', 60));

        foreach ($results as $stage => $result) {
            $passed = $result['exit_code'] === 0;
            $failures += $passed ? 0 : 1;

            printf(
                '%-16s %-10s %s'.PHP_EOL,
                $stage,
                $passed ? 'PASS' : 'FAIL',
                number_format($result['duration'], 2).'s',
            );
        }

        printf('%s'.PHP_EOL, str_repeat('-', 60));

        $notRun = array_diff($this->plannedStages, array_keys($results));

        if ($notRun !== []) {
            printf('Not run: %s'.PHP_EOL, implode(', ', $notRun));
        }

        printf(
            $failures === 0
                ? 'All '.count($results).' stage(s) passed.'.PHP_EOL
                : $failures.' stage(s) failed.'.PHP_EOL,
        );

        return $failures === 0 ? 0 : 1;
    }

    private function wantsFix(): bool
    {
        return array_key_exists('--fix', $this->options);
    }

    private function wantsFullRun(): bool
    {
        return array_key_exists('--full', $this->options);
    }

    private function wantsHelp(): bool
    {
        return array_key_exists('--help', $this->options);
    }

    private function printHelp(): void
    {
        $options = [
            '--fix' => 'Write changes (Pint and Rector both stop reporting and start writing)',
            '--dirty' => 'Only look at files changed according to Git',
            '--full' => 'Also run insights and type-coverage',
            '--only=<stages>' => 'Run only these stages',
            '--skip=<stages>' => 'Skip these stages',
            '--help' => 'Show this help',
        ];

        printf('Usage: %s [options]'.PHP_EOL.PHP_EOL, basename(__FILE__));
        printf('Stages: %s'.PHP_EOL.PHP_EOL, implode(', ', array_keys(self::STAGES)));
        foreach ($options as $option => $description) {
            printf('  %-16s %s'.PHP_EOL, $option, $description);
        }
    }
}

try {
    exit(CheckRunner::make()->run());
} catch (RuntimeException $exception) {
    fwrite(STDERR, 'ERROR: '.$exception->getMessage().PHP_EOL);

    exit(1);
}
