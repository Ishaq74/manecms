<?php

use App\Models\User;
use Livewire\Livewire;

it('shows the catalogue to a developer', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('mane.catalog'))
        ->assertOk()
        ->assertSee('ManeUI catalogue');
});

it('refuses the catalogue outside local development and tests', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    Livewire::actingAs(User::factory()->create())
        ->test('pages::mane.catalog')
        ->assertNotFound();
});
