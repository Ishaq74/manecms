<?php

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Authorization\Enums\SystemRole;
use App\Domain\Tenancy\Models\TenantInvitation;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Models\Workspace;
use App\Models\User;

/**
 * True when the focused element, or the wrapper drawing its ring, shows a focus indicator.
 */
const FOCUS_IS_VISIBLE = "(() => { let element = document.activeElement; for (let depth = 0; element && depth < 3; depth++, element = element.parentElement) { const style = getComputedStyle(element); if ((style.outlineStyle !== 'none' && parseFloat(style.outlineWidth) > 0) || style.boxShadow !== 'none') { return true; } } return false; })()";

const PAGE_DOES_NOT_OVERFLOW = 'document.documentElement.scrollWidth <= document.documentElement.clientWidth';

/**
 * Sign in an owner whose workspace has a few audit events.
 */
function signInOwnerWithAudit(): Workspace
{
    [$user, $workspace, $member] = joinWorkspace(SystemRole::Owner);

    asOwner(fn () => AuditEvent::factory()->count(3)->create(['tenant_id' => $member->tenant_id]));
    TenantMember::factory()->admin()->for($member->tenant)->create();
    TenantInvitation::factory()->for($member->tenant)->create(['email' => 'pending@example.test']);

    test()->actingAs($user);

    return $workspace;
}

it('has no serious accessibility issue on the guest pages', function (string $route): void {
    visit(route($route))
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues();
})->with(['login', 'register']);

it('has no serious accessibility issue on the application pages, dark and light', function (string $page, string $mode): void {
    $workspace = signInOwnerWithAudit();

    $url = match ($page) {
        'workspace' => route('workspace.home', $workspace),
        'settings' => route('profile.edit'),
        'audit' => route('audit.index', $workspace),
        'members' => route('members.index', $workspace),
        'roles' => route('roles.index', $workspace),
        'space settings' => route('tenant.settings', $workspace),
        'sessions' => route('sessions.index'),
        'catalogue' => route('mane.catalog'),
    };

    $page = visit($url);
    $page->script("localStorage.setItem('dark-theme', '{$mode}')");

    $page->refresh()
        ->assertScript("document.documentElement.classList.contains('dark') === ".($mode === 'dark' ? 'true' : 'false'))
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues();
})->with(['workspace', 'settings', 'audit', 'members', 'roles', 'space settings', 'sessions', 'catalogue'])->with(['dark', 'light']);

it('opens the invitation and member dialogs without accessibility issues', function (): void {
    $workspace = signInOwnerWithAudit();

    visit(route('members.index', $workspace))
        ->click('@invite-member-button')
        ->assertSee('Send the invitation')
        ->assertNoAccessibilityIssues()
        ->assertNoJavaScriptErrors();
});

it('shows an invitation to its recipient without accessibility issues', function (): void {
    $invitation = TenantInvitation::factory()->withToken(str_repeat('t', 48))->create(['email' => 'invitee@example.test']);
    test()->actingAs(User::factory()->create(['email' => 'invitee@example.test']));

    visit(route('invitations.show', ['invitation' => $invitation->id, 'token' => str_repeat('t', 48)]))
        ->assertSee('Join the space')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues();
});

it('walks the login form with the keyboard and keeps the focus visible', function (): void {
    $page = visit(route('login'))->keys('input[name="email"]', 'Tab');

    foreach (range(1, 4) as $step) {
        $page->assertScript("document.activeElement !== document.body && document.activeElement.closest('form') !== null")
            ->assertScript(FOCUS_IS_VISIBLE)
            ->keys(':focus', 'Tab');
    }

    $page->assertNoJavaScriptErrors();
});

it('opens and closes a modal from the keyboard', function (): void {
    test()->actingAs(User::factory()->create());

    visit(route('mane.catalog'))
        ->keys('@catalog-open-modal', 'Enter')
        ->assertSee('Escape closes it and focus returns to the trigger.')
        ->keys(':focus', 'Escape')
        ->assertDontSee('Escape closes it and focus returns to the trigger.')
        ->assertNoJavaScriptErrors();
});

it('sorts a table from the keyboard and announces the order', function (): void {
    $workspace = signInOwnerWithAudit();

    visit(route('audit.index', $workspace))
        ->assertScript("document.querySelector('[data-test=\"data-table-sort-action\"]').closest('th').getAttribute('aria-sort') === 'none'")
        ->keys('@data-table-sort-action', 'Enter')
        ->assertScript("document.querySelector('[data-test=\"data-table-sort-action\"]').closest('th').getAttribute('aria-sort') === 'ascending'")
        ->assertScript(FOCUS_IS_VISIBLE)
        ->assertNoJavaScriptErrors();
});

it('lays out the catalogue right to left and in every density without overflowing', function (): void {
    test()->actingAs(User::factory()->create());

    $page = visit(route('mane.catalog'))
        ->assertScript(PAGE_DOES_NOT_OVERFLOW)
        ->click('@catalog-toggle-direction')
        ->assertScript("document.documentElement.dir === 'rtl'")
        ->assertScript(PAGE_DOES_NOT_OVERFLOW);

    foreach (['compact', 'dense'] as $density) {
        $page->select('#catalog-density', $density)
            ->assertScript("document.querySelector('[data-test=\"mane-catalog\"]').dataset.density === '{$density}'")
            ->assertScript(PAGE_DOES_NOT_OVERFLOW);
    }

    $page->assertNoJavaScriptErrors();
});

it('keeps a disabled submit focusable, announced and inert (§407)', function (): void {
    test()->actingAs(User::factory()->create());

    visit(route('mane.catalog'))
        ->type('@catalog-disabled-input', 'Ada')
        ->keys('@catalog-disabled-input', 'Tab')
        ->assertScript("document.activeElement.dataset.test === 'catalog-disabled-submit'")
        ->assertScript("document.activeElement.getAttribute('aria-disabled') === 'true'")
        ->assertScript(FOCUS_IS_VISIBLE)
        ->keys(':focus', ['Enter', 'Space'])
        ->keys('@catalog-disabled-input', 'Enter')
        ->wait(1)
        ->assertSeeIn('@catalog-submissions', 'Submissions: 0')
        ->assertNoJavaScriptErrors();
});
