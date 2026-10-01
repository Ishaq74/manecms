<?php

namespace App\View\Components\TallStackUi\Colors;

use Illuminate\View\Component;

/**
 * Colours for `<x-checkbox />`, `<x-checkbox.group />`, `<x-radio />` and
 * `<x-radio.group />`.
 *
 * The vendor check glyph is text-primary-900 on a primary-500 fill, which is
 * capped at 2.94:1 because step 900 is dark and step 500 carries white text.
 * No ramp can fix both at once: the same fill needs a near-white glyph in dark
 * mode, where the vendor uses step 200. The glyph therefore uses step 50 in
 * both modes, the pairing that already reaches 4.57:1.
 */
class SelectionColors
{
    /**
     * Background colors.
     *
     * @return array<string, string>
     */
    public function backgroundColors(Component $component): array
    {
        return [
            'primary' => 'has-checked:bg-primary-50 dark:has-checked:bg-primary-900/20',
            'secondary' => 'has-checked:bg-secondary-50 dark:has-checked:bg-secondary-900/20',
        ];
    }

    /**
     * Border colors.
     *
     * @return array<string, string>
     */
    public function borderColors(Component $component): array
    {
        return [
            'primary' => 'has-checked:border-primary-500 dark:has-checked:border-primary-400',
            'secondary' => 'has-checked:border-secondary-500 dark:has-checked:border-secondary-400',
        ];
    }

    /**
     * Control colors.
     *
     * @return array<string, string>
     */
    public function controlColors(Component $component): array
    {
        return [
            'primary' => 'text-primary-500 focus:ring-primary-500 dark:ring-offset-dark-900',
            'secondary' => 'text-secondary-500 focus:ring-secondary-500 dark:ring-offset-dark-900',
        ];
    }

    /**
     * Muted colors.
     *
     * @return array<string, string>
     */
    public function mutedColors(Component $component): array
    {
        return [
            'primary' => 'group-has-checked:text-primary-700 dark:group-has-checked:text-primary-300',
            'secondary' => 'group-has-checked:text-secondary-700 dark:group-has-checked:text-secondary-300',
        ];
    }

    /**
     * Solid colors.
     *
     * @return array<string, string>
     */
    public function solidColors(Component $component): array
    {
        return [
            'primary' => 'has-checked:bg-primary-500',
            'secondary' => 'has-checked:bg-secondary-500',
        ];
    }

    /**
     * Text colors.
     *
     * @return array<string, string>
     */
    public function textColors(Component $component): array
    {
        return [
            'primary' => 'group-has-checked:text-primary-50 dark:group-has-checked:text-primary-50',
            'secondary' => 'group-has-checked:text-secondary-50 dark:group-has-checked:text-secondary-50',
        ];
    }
}
