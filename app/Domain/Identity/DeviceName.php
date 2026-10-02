<?php

namespace App\Domain\Identity;

/**
 * A short, human name for a user agent ("Firefox on Windows"); not a parser,
 * only what a person needs to recognise their own device.
 */
final class DeviceName
{
    private const array BROWSERS = [
        'Edg/' => 'Edge',
        'OPR/' => 'Opera',
        'Firefox/' => 'Firefox',
        'Chrome/' => 'Chrome',
        'Safari/' => 'Safari',
    ];

    private const array SYSTEMS = [
        'Windows' => 'Windows',
        'Android' => 'Android',
        'iPhone' => 'iOS',
        'iPad' => 'iPadOS',
        'Mac OS X' => 'macOS',
        'Linux' => 'Linux',
    ];

    public static function from(?string $userAgent): string
    {
        $userAgent ??= '';
        $browser = self::firstMatch(self::BROWSERS, $userAgent);
        $system = self::firstMatch(self::SYSTEMS, $userAgent);

        return match (true) {
            $browser !== null && $system !== null => __(':browser on :system', ['browser' => $browser, 'system' => $system]),
            $browser !== null => $browser,
            $system !== null => $system,
            default => __('Unknown device'),
        };
    }

    /**
     * @param  array<string, string>  $candidates
     */
    private static function firstMatch(array $candidates, string $userAgent): ?string
    {
        foreach ($candidates as $needle => $name) {
            if (str_contains($userAgent, $needle)) {
                return $name;
            }
        }

        return null;
    }
}
