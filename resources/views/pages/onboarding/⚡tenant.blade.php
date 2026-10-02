<?php

use App\Domain\Tenancy\Actions\CreateTenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::sidebar')] #[Title('Create your space')] class extends Component {
    public string $name = '';

    /**
     * Create the tenant, its first workspace and the owner membership.
     */
    public function createTenant(CreateTenant $createTenant): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $workspace = $createTenant($user, $this->name);

        $this->redirectRoute('workspace.home', ['workspace' => $workspace->id], navigate: true);
    }
}; ?>

<section class="mx-auto flex w-full max-w-lg flex-col gap-6">
    <x-mane::page-header :title="__('Create your space')" :description="__('Name the company, association or project you manage. You can add more workspaces later.')" />

    <x-mane::card>
        <x-mane::form wire:submit="createTenant" :dirty-notice="false">
            <x-mane::input wire:model="name" :label="__('Name')" type="text" required autofocus maxlength="120" data-test="tenant-name" />

            <x-mane::button type="submit" block loading="createTenant" data-test="create-tenant-button" :text="__('Create')" />
        </x-mane::form>
    </x-mane::card>
</section>
