<?php

use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Models\Workspace;

it('sends a member back to the workspace used last', function (): void {
    [$user, , $member] = joinWorkspace();
    $last = Workspace::factory()->for($member->tenant)->create();
    $member->update(['last_workspace_id' => $last->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('workspace.home', $last));
});

it('falls back to the oldest active workspace when the last one is archived', function (): void {
    [$user, $oldest, $member] = joinWorkspace();
    $oldest->forceFill(['created_at' => now()->subDays(2)])->save();
    Workspace::factory()->for($member->tenant)->create(['created_at' => now()->subDay()]);
    $archived = Workspace::factory()->for($member->tenant)->archived()->create();
    $member->update(['last_workspace_id' => $archived->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('workspace.home', $oldest));
});

it('picks the most recently used workspace across two tenants', function (): void {
    [$user, , $firstMembership] = joinWorkspace();
    $secondMembership = TenantMember::factory()->owner()->for($user)->create();
    $secondWorkspace = Workspace::factory()->for($secondMembership->tenant)->create();
    $firstWorkspace = Workspace::factory()->for($firstMembership->tenant)->create();

    $firstMembership->forceFill(['last_workspace_id' => $firstWorkspace->id, 'updated_at' => now()->subHour()])->save();
    $secondMembership->forceFill(['last_workspace_id' => $secondWorkspace->id, 'updated_at' => now()])->save();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('workspace.home', $secondWorkspace));
});
