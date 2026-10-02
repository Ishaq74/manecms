<?php

use App\Enums\TextDirection;

it('writes right-to-left languages right to left', function (string $locale, TextDirection $direction): void {
    expect(TextDirection::forLocale($locale))->toBe($direction);
})->with([
    ['ar', TextDirection::RightToLeft],
    ['ar_MA', TextDirection::RightToLeft],
    ['he-IL', TextDirection::RightToLeft],
    ['fa', TextDirection::RightToLeft],
    ['fr', TextDirection::LeftToRight],
    ['en_GB', TextDirection::LeftToRight],
    ['arn', TextDirection::LeftToRight],
]);
