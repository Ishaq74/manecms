<?php

namespace App\Enums;

/**
 * Writing direction of the interface, derived from the active locale (§38).
 */
enum TextDirection: string
{
    case LeftToRight = 'ltr';
    case RightToLeft = 'rtl';

    /**
     * Languages written right to left, by ISO 639-1 code.
     */
    private const array RIGHT_TO_LEFT_LANGUAGES = ['ar', 'dv', 'fa', 'he', 'ku', 'ps', 'sd', 'ug', 'ur', 'yi'];

    public static function forLocale(string $locale): self
    {
        $language = strtolower(strtok(str_replace('_', '-', $locale), '-') ?: $locale);

        return in_array($language, self::RIGHT_TO_LEFT_LANGUAGES, true) ? self::RightToLeft : self::LeftToRight;
    }

    public static function current(): self
    {
        return self::forLocale(app()->getLocale());
    }
}
