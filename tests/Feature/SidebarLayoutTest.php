<?php

use App\Models\User;
use Illuminate\Support\Facades\Blade;

/**
 * The sidebar layout is the authenticated application shell, so it is always
 * rendered as a signed-in user, the way the app pages use it.
 */
function renderSidebarLayout(): string
{
    test()->actingAs(User::factory()->create());

    return Blade::render(
        "@component('layouts::sidebar')<p>content</p>@endcomponent",
        [],
        deleteCachedView: true,
    );
}

it('renders the sidebar layout', function (): void {
    $html = renderSidebarLayout();

    expect($html)
        ->toContain('<!DOCTYPE html>')
        ->toContain('tsui.side-bar')
        ->toContain('View site')
        ->toContain('max-w-7xl');
});

it('keeps the sidebar layout inside the palette', function (): void {
    // primary, secondary and dark are the only ramps the design system defines.
    expect(renderSidebarLayout())
        ->not->toMatch('/\b(?:gray|slate|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose|accent)-/');
});

it('does not stack the sidebar padding twice', function (): void {
    expect(renderSidebarLayout())->not->toContain('p-10');
});
