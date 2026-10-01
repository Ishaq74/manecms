<?php

use App\Models\User;

/**
 * The user menu offers Dashboard, Profile and Log out.
 *
 * Log out is a POST, so it cannot be an anchor. Without an href the dropdown
 * item renders a <button>, which lets the row keep the menu styling while the
 * surrounding form submits it.
 */
it('expose dashboard, profil et deconnexion', function (): void {
    [$user, $workspace] = joinWorkspace();
    $this->actingAs($user);

    $html = $this->get(route('workspace.home', $workspace))->assertOk()->getContent();

    // The markup wraps slot content across lines, so compare without whitespace.
    $flat = (string) preg_replace('/\s+/', ' ', $html);

    expect($flat)
        ->toContain('href="'.route('dashboard').'"')
        ->toContain('href="'.route('profile.edit').'"')
        ->toContain('Dashboard')
        ->toContain('Profile')
        ->toContain('Log out');

    // Logout must be a real form POST, not a link.
    expect($flat)->toContain('<form method="POST" action="'.route('logout').'"');

    // And it must be a menu row, so the button carries the item styling.
    preg_match('/<button [^>]*data-test="desktop-user-menu-logout"[^>]*>/', $flat, $matches);

    expect($matches)->not->toBeEmpty();
    expect($matches[0])
        ->toContain('role="menuitem"')
        ->toContain('type="submit"')
        ->toContain('hover:bg-dark-100');

    // The old entry pointed at the profile page under a "Settings" label.
    expect($flat)->not->toContain('>Settings<');
});

it('renders the user menu once per breakpoint in the top header', function (): void {
    $this->actingAs(User::factory()->create());

    // The landing page uses the shell header, which carries both instances.
    $flat = (string) preg_replace('/\s+/', ' ', $this->get(route('home'))->assertOk()->getContent());

    expect($flat)
        ->toContain('data-test="desktop-user-menu-button"')
        ->toContain('data-test="mobile-user-menu-button"')
        ->toContain('data-test="desktop-user-menu-logout"')
        ->toContain('data-test="mobile-user-menu-logout"');

    // Every hook stays unique across the whole document, the hamburger included.
    preg_match_all('/data-test="([^"]+)"/', $flat, $hooks);

    expect(array_filter(array_count_values($hooks[1]), fn (int $count): bool => $count > 1))->toBeEmpty();
});
