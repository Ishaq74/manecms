<?php

/**
 * The navigation used to print Blade directives straight into the markup, which
 * shipped `@if="@if" request="request"` to the browser. Blade does not compile
 * directives placed inside a component tag, so these guards exist to catch that
 * class of mistake.
 */
test('le header ne laisse pas de directive Blade dans le HTML', function (): void {
    $html = $this->get(route('home'))->assertOk()->getContent();

    foreach (['@if', '@endif', '@class', '@foreach', '@endforeach', '@php'] as $directive) {
        expect($html)->not->toContain($directive);
    }
});

test('la navigation principale rend des liens propres', function (): void {
    $html = $this->get(route('home'))->assertOk()->getContent();

    expect($html)->toContain('<a href=', 'Home');

    expect($html)->not->toContain('<x-link', 'navigate="navigate" class="inline-flex text-md text-primary-500 rounded-lg');
});

test('le lien actif porte aria-current', function (): void {
    $html = $this->get(route('home'))->assertOk()->getContent();

    expect($html)->toContain('aria-current="page"');
});
