<?php

test('guests are redirected to the login page', function (): void {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users are taken from the dashboard to their workspace', function (): void {
    [$user, $workspace] = joinWorkspace();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('workspace.home', $workspace));
});
