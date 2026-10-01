<?php

use App\Domain\Tenancy\Actions\CreateWorkspace;
use App\Domain\Tenancy\Models\Workspace;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::sidebar')] #[Title('New workspace')] class extends Component {
    public string $name = '';

    public function mount(): void
    {
        $this->authorize('create', Workspace::class);
    }

    /**
     * Create a workspace in the current tenant and open it.
     */
    public function createWorkspace(CreateWorkspace $createWorkspace): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $workspace = $createWorkspace($user, $this->name);

        $this->reset('name');
        $this->redirectRoute('workspace.home', ['workspace' => $workspace->id], navigate: true);
    }
}; ?>

<section class="mx-auto flex w-full max-w-lg flex-col gap-6">
    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-semibold tracking-tight text-dark-900 dark:text-white">
            {{ __('New workspace') }}
        </h1>

        <p class="text-sm text-dark-500 dark:text-dark-400">
            {{ __('A workspace separates the content and activity of a team, brand or project.') }}
        </p>
    </div>

    <form wire:submit="createWorkspace" class="space-y-6">
        <x-input
            wire:model="name"
            :label="__('Name')"
            type="text"
            required
            autofocus
            maxlength="120"
            data-test="workspace-name-input"
        />

        <x-button
            submit
            block
            wire:loading.attr="disabled"
            wire:target="createWorkspace"
            data-test="create-workspace-button"
            :text="__('Create')"
        />
    </form>
</section>
