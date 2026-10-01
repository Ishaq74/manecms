<?php

namespace App\View\Components\TallStackUi\Colors;

use Illuminate\View\Component;

/**
 * Colours for `<x-link />`.
 *
 * The vendor default is text-primary-500 in both modes. That clears 4.5:1 on
 * white at 4.77:1 but drops to 3.08:1 on the dark page, because step 500 is
 * dark by design. The dark variant moves to step 400, which reaches 8.52:1.
 */
class LinkColors
{
    /**
     * Text colors.
     *
     * @return array<string, string>
     */
    public function textColors(Component $component): array
    {
        return [
            'primary' => 'text-primary-600 dark:text-primary-400',
            'secondary' => 'text-secondary-600 dark:text-secondary-400',
        ];
    }
}
