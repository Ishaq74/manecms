<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ViewException;

function renderManeUi(string $template, array $data = []): string
{
    return Blade::render($template, $data, deleteCachedView: true);
}

it('keeps a disabled button focusable, announced and inert', function (): void {
    $html = renderManeUi('<x-mane::button type="submit" href="/danger" wire:click="destroy" x-on:click="go()" disabled text="Delete" />');

    expect($html)
        ->toContain('aria-disabled="true"')
        ->toContain('type="button"')
        ->toContain('data-mane-submit')
        ->not->toContain('wire:click')
        ->not->toContain('x-on:click')
        ->not->toContain('href=')
        ->not->toMatch('/\sdisabled(=|\s|>)/');
});

it('marks an enabled submit button for the form guard', function (): void {
    expect(renderManeUi('<x-mane::button type="submit" text="Save" />'))
        ->toContain('type="submit"')
        ->toContain('data-mane-submit')
        ->not->toContain('aria-disabled');
});

it('scopes the loading state of a button to its action', function (): void {
    expect(renderManeUi('<div><x-mane::button loading="save" wire:click="save" text="Save" /></div>'))
        ->toContain('wire:target="save"');
});

it('rejects an unknown variant instead of inventing a style', function (string $template): void {
    renderManeUi($template);
})->with([
    'button' => '<x-mane::button variant="fancy" />',
    'status' => '<x-mane::status tone="purple" text="x" />',
    'empty state' => '<x-mane::empty-state kind="unknown" />',
])->throws(ViewException::class, 'Unknown ManeUI');

it('names an icon button after its label', function (): void {
    expect(renderManeUi('<x-mane::icon-button icon="trash" label="Remove passkey" />'))
        ->toContain('aria-label="Remove passkey"');
});

it('ties the select label, hint and error to the control', function (): void {
    view()->share('errors', (new ViewErrorBag)->put('default', new MessageBag(['role' => ['Pick a role.']])));

    $html = renderManeUi('<x-mane::select name="role" label="Role" hint="Who can do what." :options="[\'admin\' => \'Admin\']" />');

    expect($html)
        ->toContain('<label for="mane-select-role"')
        ->toContain('id="mane-select-role"')
        ->toContain('aria-describedby="mane-select-role-hint"')
        ->toContain('aria-invalid="true"')
        ->toContain('Pick a role.');
});

it('never conveys a status by colour alone', function (string $tone): void {
    expect(renderManeUi("<x-mane::status tone=\"{$tone}\" text=\"Overdue\" />"))
        ->toContain('<svg')
        ->toContain('Overdue');
})->with(['success', 'warning', 'danger', 'info', 'muted']);

it('interrupts screen readers for errors only', function (string $tone, string $role): void {
    expect(renderManeUi("<x-mane::alert tone=\"{$tone}\" title=\"Heads up\" />"))->toContain("role=\"{$role}\"");
})->with([
    ['danger', 'alert'],
    ['warning', 'alert'],
    ['info', 'status'],
    ['success', 'status'],
]);

it('marks the current page of a breadcrumb', function (): void {
    $html = renderManeUi('<x-mane::breadcrumb :items="[[\'label\' => \'Home\', \'href\' => \'/\'], [\'label\' => \'Profile\']]" />');

    expect($html)
        ->toContain('<a href="/"')
        ->toMatch('/aria-current="page"[^>]*>Profile</');
});

it('tells the empty, first use, no access, no result and error cases apart', function (): void {
    $titles = array_map(
        fn (string $kind): string => strip_tags(renderManeUi("<x-mane::empty-state kind=\"{$kind}\" />")),
        ['empty', 'first-use', 'no-access', 'no-results', 'error'],
    );

    expect(array_unique(array_map('trim', $titles)))->toHaveCount(5);
});

it('announces loading states politely', function (): void {
    expect(renderManeUi('<x-mane::loading-state kind="queued" />'))
        ->toContain('role="status"')
        ->toContain('aria-live="polite"')
        ->toContain('Queued, it will run shortly.');
});

it('writes the page right to left for a right-to-left locale', function (): void {
    app()->setLocale('ar');

    $this->get(route('login'))->assertSee('dir="rtl"', escape: false);

    app()->setLocale('en');

    $this->get(route('login'))->assertSee('dir="ltr"', escape: false);
});
