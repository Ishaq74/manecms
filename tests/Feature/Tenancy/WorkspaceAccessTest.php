<?php

use App\Domain\Authorization\Enums\SystemRole;
use App\Domain\Tenancy\Exceptions\TenantContextRequired;
use App\Domain\Tenancy\Models\Workspace;
use App\Models\User;
use Livewire\Livewire;

it('shows a workspace to every member of its tenant', function (SystemRole $role): void {
    [$user, $workspace] = joinWorkspace($role);

    $this->actingAs($user)
        ->get(route('workspace.home', $workspace))
        ->assertOk()
        ->assertSee($workspace->name);
})->with(SystemRole::cases());

it('answers 404 to a user outside the tenant', function (): void {
    [, $workspace] = joinWorkspace();

    $this->actingAs(User::factory()->create())
        ->get(route('workspace.home', $workspace))
        ->assertNotFound();
});

it('answers 404 to a malformed workspace identifier', function (): void {
    [$user] = joinWorkspace();

    $this->actingAs($user)
        ->get('/w/not-a-ulid')
        ->assertNotFound();
});

it('answers 404 to an archived workspace', function (): void {
    [$user, $workspace] = joinWorkspace();
    asOwner(fn (): bool => $workspace->forceFill(['archived_at' => now()])->save());

    $this->actingAs($user)
        ->get(route('workspace.home', $workspace))
        ->assertNotFound();
});

it('redirects guests to the login page', function (): void {
    [, $workspace] = joinWorkspace();

    $this->get(route('workspace.home', $workspace))->assertRedirect(route('login'));
});

it('remembers the workspace a member opens', function (): void {
    [$user, $workspace, $member] = joinWorkspace();
    $other = Workspace::factory()->for($member->tenant)->create();

    $this->actingAs($user)->get(route('workspace.home', $other))->assertOk();

    expect(asOwner(fn (): ?string => $member->fresh()?->last_workspace_id))->toBe($other->id)
        ->and($workspace->id)->not->toBe($other->id);
});

it('fails closed when a component runs without a tenant context', function (): void {
    [$user] = joinWorkspace();

    $causes = [];

    try {
        Livewire::actingAs($user)->test('pages::workspaces.home');
    } catch (Throwable $exception) {
        // Blade wraps the failure once per nested view, so collect the whole chain.
        for ($cause = $exception; $cause !== null; $cause = $cause->getPrevious()) {
            $causes[] = $cause::class;
        }
    }

    expect($causes)->toContain(TenantContextRequired::class);
});
