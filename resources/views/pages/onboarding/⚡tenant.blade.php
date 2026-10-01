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
    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-semibold tracking-tight text-dark-900 dark:text-white">
            {{ __('Create your space') }}
        </h1>

        <p class="text-sm text-dark-500 dark:text-dark-400">
            {{ __('Name the company, association or project you manage. You can add more workspaces later.') }}
        </p>
    </div>

    <form wire:submit="createTenant" class="space-y-6">
        <x-input
            wire:model="name"
            :label="__('Name')"
            type="text"
            required
            autofocus
            maxlength="120"
            data-test="tenant-name"
        />

        <x-button
            submit
            block
            wire:loading.attr="disabled"
            wire:target="createTenant"
            data-test="create-tenant-button"
            :text="__('Create')"
        />
    </form>
</section>
