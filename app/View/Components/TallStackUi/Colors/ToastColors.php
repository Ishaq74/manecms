<?php

namespace App\View\Components\TallStackUi\Colors;

use Illuminate\View\Component;

/**
 * Colours for `<x-toast />`.
 *
 * The vendor maps the five toast types onto Tailwind's raw status palettes
 * (green-500, red-500, blue-500, yellow-500) for the background and the lighter
 * steps for the icon. Those families are not part of the project design system,
 * so every type is remapped onto primary, secondary and dark:
 *
 *  success  -> secondary, the closest hue on the palette
 *  error    -> primary,   the darkest step reads as the most severe
 *  info     -> primary
 *  warning  -> dark-600
 *  question -> secondary
 *
 * The confirmation pair is on-palette already. The rejection step moves from
 * red-700 to primary-700, which is the same visual weight on dark backgrounds.
 */
class ToastColors
{
    /**
     * Icon colors.
     *
     * @return array<string, string>
     */
    public function iconColors(Component $component): array
    {
        return [
            'success' => 'text-secondary-500 dark:text-secondary-400',
            'error' => 'text-primary-500 dark:text-primary-400',
            'info' => 'text-primary-500 dark:text-primary-400',
            'warning' => 'text-dark-500 dark:text-dark-400',
            'question' => 'text-secondary-500 dark:text-secondary-400',
        ];
    }

    /**
     * Background colors.
     *
     * @return array<string, string>
     */
    public function backgroundColors(Component $component): array
    {
        return [
            'success' => 'bg-secondary-500! dark:bg-secondary-600!',
            'error' => 'bg-primary-500! dark:bg-primary-600!',
            'info' => 'bg-primary-500! dark:bg-primary-600!',
            'warning' => 'bg-dark-500! dark:bg-dark-600!',
            'question' => 'bg-primary-500! dark:bg-primary-600!',
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
            'confirm' => 'text-primary-600 dark:text-primary-400',
            'cancel' => 'text-primary-700 dark:text-primary-400',
        ];
    }

    /**
     * Colorful colors, used on the solid confirmation buttons.
     *
     * @return array<string, string>
     */
    public function colorfulColors(Component $component): array
    {
        return [
            'confirm' => 'text-white font-bold!',
            'cancel' => 'text-white/80',
        ];
    }
}
