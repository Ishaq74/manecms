<?php

namespace App\View\Components\TallStackUi\Colors;

use Illuminate\View\Component;

/**
 * Colours for `<x-button.circle />`.
 *
 * Only primary and secondary are declared; every other colour falls through to
 * the package default, which already meets WCAG AA against this palette.
 *
 * Four vendor defaults cannot be reached with tokens alone, because the same
 * ramp step is asked to serve two opposite roles:
 *
 *  - `light` pairs text-600 with bg-300, then bg-400 on hover. Step 400 is far
 *    too light for text-600, so the background moves to 200 and hover to 300.
 *  - `outline` and `flat` use text-500 in dark mode. Step 500 is dark by design
 *    (white text sits on it), so it fails as text on the dark page: both switch
 *    to 400.
 *  - The focus rings of every style use 500, 600 or 700 in dark mode. All three
 *    are darker than the page, so they vanish: 2.40:1 and 1.74:1 against the
 *    3:1 that WCAG 1.4.11 requires of a focus indicator. Step 400 reaches 8.52:1
 *    on the page and 6.91:1 on a dark-800 surface, so dark focus uses 400.
 *
 * The `red` entries cover the destructive buttons, which the profile and
 * security pages ask for with color="red". Red is outside the design system, so
 * they take the primary ramp, where text-primary-50 on primary-500 reaches the
 * same 4.57:1 the primary button already clears.
 */
class CircleButtonColors
{
    /**
     * Background colors.
     *
     * @return array<string, array<string, string>>
     */
    public function backgroundColors(Component $component): array
    {
        return [
            'solid' => [
                'primary' => 'text-primary-50 ring-primary-500 bg-primary-500 focus:bg-primary-600 hover:bg-primary-600 border-transparent focus:ring-offset-2 dark:focus:ring-offset-dark-900 dark:focus:ring-primary-400 dark:bg-primary-700 dark:hover:bg-primary-600 dark:hover:ring-primary-600',
                'secondary' => 'text-secondary-50 ring-secondary-500 bg-secondary-500 focus:bg-secondary-600 hover:bg-secondary-600 border-transparent focus:ring-offset-2 dark:focus:ring-offset-dark-900 dark:focus:ring-secondary-400 dark:bg-secondary-700 dark:hover:bg-secondary-600 dark:hover:ring-secondary-600',
                'red' => 'text-primary-50 ring-primary-500 bg-primary-500 focus:bg-primary-700 hover:bg-primary-700 border-transparent focus:ring-offset-2 dark:focus:ring-offset-dark-900 dark:focus:ring-primary-400 dark:bg-primary-600 dark:hover:bg-primary-700 dark:hover:ring-primary-400',
            ],
            'outline' => [
                'primary' => 'text-primary-600 border-primary-600 hover:bg-primary-400/20 focus:ring-offset-0 focus:text-primary-700 focus:bg-primary-400/20 focus:ring-primary-600 hover:text-primary-700 dark:hover:text-primary-400 dark:hover:bg-primary-600/20 dark:focus:border-transparent dark:focus:text-primary-400 dark:focus:bg-primary-600/20 dark:focus:ring-primary-400',
                'secondary' => 'text-secondary-600 border-secondary-600 hover:bg-secondary-400/20 focus:ring-offset-0 focus:text-secondary-700 focus:bg-secondary-400/20 focus:ring-secondary-600 hover:text-secondary-700 dark:hover:text-secondary-400 dark:hover:bg-secondary-600/20 dark:focus:border-transparent dark:focus:text-secondary-400 dark:focus:bg-secondary-600/20 dark:focus:ring-secondary-400',
                'red' => 'text-primary-700 border-primary-600 hover:bg-primary-200/40 focus:ring-offset-0 focus:text-primary-800 focus:bg-primary-200/40 focus:ring-primary-600 hover:text-primary-800 dark:hover:text-primary-300 dark:hover:bg-primary-600/20 dark:focus:border-transparent dark:focus:text-primary-300 dark:focus:bg-primary-600/20 dark:focus:ring-primary-400',
            ],
            'light' => [
                'primary' => 'text-primary-600 ring-primary-400 bg-primary-200 hover:bg-primary-300 border-transparent focus:ring-offset-2 dark:focus:text-primary-400 dark:focus:ring-offset-dark-900 dark:focus:ring-primary-500 dark:bg-primary-500/20 dark:hover:bg-primary-500/30 dark:text-primary-400 dark:hover:ring-primary-600',
                'secondary' => 'text-secondary-600 ring-secondary-400 bg-secondary-200 hover:bg-secondary-300 border-transparent focus:ring-offset-2 dark:focus:text-secondary-400 dark:focus:ring-offset-dark-900 dark:focus:ring-secondary-500 dark:bg-secondary-500/20 dark:hover:bg-secondary-500/30 dark:text-secondary-400 dark:hover:ring-secondary-600',
            ],
            'flat' => [
                'primary' => 'focus:ring-offset-background-white text-primary-600 dark:text-primary-400 hover:text-primary-700 hover:bg-primary-200 dark:hover:text-primary-400 dark:hover:bg-primary-500/10 focus:ring-offset-0 focus:text-primary-700 focus:bg-primary-200 focus:ring-primary-600 dark:focus:text-primary-400 dark:focus:bg-primary-500/10 dark:focus:ring-primary-400',
                'secondary' => 'focus:ring-offset-background-white text-secondary-600 dark:text-secondary-400 hover:text-secondary-700 hover:bg-secondary-200 dark:hover:text-secondary-400 dark:hover:bg-secondary-500/10 focus:ring-offset-0 focus:text-secondary-700 focus:bg-secondary-200 focus:ring-secondary-600 dark:focus:text-secondary-400 dark:focus:bg-secondary-500/10 dark:focus:ring-secondary-400',
            ],
        ];
    }

    /**
     * Icon colors.
     *
     * @return array<string, array<string, string>>
     */
    public function iconColors(Component $component): array
    {
        return [
            'solid' => [
                'primary' => 'text-primary-50',
                'secondary' => 'text-secondary-50',
            ],
            'outline' => [
                'primary' => 'text-primary-600 dark:text-primary-400',
                'secondary' => 'text-secondary-600 dark:text-secondary-400',
            ],
            'light' => [
                'primary' => 'text-primary-600 dark:text-primary-400',
                'secondary' => 'text-secondary-600 dark:text-secondary-400',
            ],
            'flat' => [
                'primary' => 'text-primary-600 dark:text-primary-400',
                'secondary' => 'text-secondary-600 dark:text-secondary-400',
            ],
        ];
    }
}
