<?php

use App\Models\User;
use DOMDocument;
use DOMXPath;

function sidebarXPath(string $html): DOMXPath
{
    $dom = new DOMDocument;
    @$dom->loadHTML($html);

    return new DOMXPath($dom);
}

it('renders the app pages with the collapsible sidebar', function (): void {
    [$user, $workspace] = joinWorkspace();

    $pages = [
        route('workspace.home', $workspace, absolute: false),
        route('workspace.create', $workspace, absolute: false),
        route('workspace.settings', $workspace, absolute: false),
        route('tenant.settings', $workspace, absolute: false),
        route('onboarding', absolute: false),
        '/settings/profile',
        '/settings/security',
        '/settings/appearance',
    ];

    foreach ($pages as $page) {
        $response = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get($page);

        $response->assertSuccessful();

        $xpath = sidebarXPath($response->getContent());

        expect($xpath->query('//nav[@aria-label="Sidebar"]')->length)->toBeGreaterThan(0);

        // Header, main and footer share the same width.
        expect($response->getContent())->toContain('max-w-7xl');
    }
});

it('keeps the user menu entries out of the sidebar navigation', function (): void {
    [$user, $workspace] = joinWorkspace();

    $xpath = sidebarXPath($this->actingAs($user)->get(route('workspace.home', $workspace))->assertOk()->getContent());

    $labels = [];

    foreach ($xpath->query('//nav[@aria-label="Sidebar"]//a') as $link) {
        $labels[trim($link->textContent)] = true;
    }

    // Dashboard and Profile live in the user menu, not twice.
    expect(array_keys($labels))->toContain('View site')
        ->not->toContain('Dashboard')
        ->not->toContain('Profile');
});

it('renders the user menu only inside the sidebars', function (): void {
    [$user, $workspace] = joinWorkspace();

    $xpath = sidebarXPath($this->actingAs($user)->get(route('workspace.home', $workspace))->assertOk()->getContent());

    // One copy in the desktop sidebar, one in the mobile one, both collapsible
    // so only one is ever visible. A third would mean the layout header carries
    // its own copy, which is the duplication this guards against.
    expect($xpath->query('//button[@data-test="desktop-user-menu-button"]')->length)->toBe(2);
});

it('does not expose the dashboard to guests', function (): void {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('keeps the dashboard out of the public navigation', function (): void {
    $html = $this->get(route('home'))->assertOk()->getContent();

    // A guest sees no route to the application at all.
    expect($html)
        ->not->toContain(route('dashboard'))
        ->not->toContain('tsui.side-bar');
});
