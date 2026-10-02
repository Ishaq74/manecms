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
    <x-mane::page-header :title="__('New workspace')" :description="__('A workspace separates the content and activity of a team, brand or project.')" />

    <x-mane::card>
        <x-mane::form wire:submit="createWorkspace" :dirty-notice="false">
            <x-mane::input wire:model="name" :label="__('Name')" type="text" required autofocus maxlength="120" data-test="workspace-name-input" />

            <x-mane::button type="submit" block loading="createWorkspace" data-test="create-workspace-button" :text="__('Create')" />
        </x-mane::form>
    </x-mane::card>
</section>
