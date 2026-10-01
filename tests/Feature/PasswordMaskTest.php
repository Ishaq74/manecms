<?php

/**
 * The password field must never be readable as plain text.
 *
 * TallStackUI 4.0.0 declared the toggle with ::type on a dynamic component.
 * Blade compiles :: attributes to literal strings, so the binding was never
 * evaluated: the input kept the package default of type="text" and the eye
 * button did nothing. 4.2.1 declares it as x-bind:type, which Alpine honours,
 * so the field is masked as soon as Alpine boots and the eye button reveals it.
 */
test('le champ password est masque par Alpine', function (): void {
    $html = $this->get(route('login'))->assertOk()->getContent();

    preg_match('/<input\b[^>]*\bname="password"[^>]*>/', $html, $matches);

    expect($matches)->not->toBeEmpty();

    $field = $matches[0];

    // Alpine drives the type from the reveal state.
    expect($field)->toContain("x-bind:type=\"!show ? 'password' : 'text'\"");

    // ::type would be compiled to a literal string and never evaluated.
    expect($field)->not->toContain('::type');

    // The reveal button calls the Alpine toggle.
    expect($html)->toContain('dusk="tallstackui_form_password_reveal"');
});
