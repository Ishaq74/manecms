<?php

use App\Domain\Tenancy\Actions\ArchiveWorkspace;
use App\Domain\Tenancy\Actions\RenameWorkspace;
use App\Domain\Tenancy\Context\TenantContext;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new #[Layout('layouts::sidebar')] #[Title('Workspace settings')] class extends Component {
    use Interactions;

    public string $name = '';

    public bool $showArchiveModal = false;

    public function mount(TenantContext $context): void
    {
        $this->authorize('update', $context->workspace());

        $this->name = $context->workspace()->name;
    }

    /**
     * Rename the current workspace.
     */
    public function renameWorkspace(RenameWorkspace $renameWorkspace, TenantContext $context): void
    {
        $renameWorkspace($this->user(), $context->workspace()->id, $this->name);

        $this->toast()->success(__('Workspace renamed.'))->send();
    }

    /**
     * Archive the current workspace and leave it.
     */
    public function archiveWorkspace(ArchiveWorkspace $archiveWorkspace, TenantContext $context): void
    {
        $archiveWorkspace($this->user(), $context->workspace()->id);

        $this->redirectRoute('dashboard', navigate: true);
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}; ?>

<section class="mx-auto flex w-full max-w-2xl flex-col gap-10">
    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold tracking-tight text-dark-900 dark:text-white">
                {{ __('Workspace settings') }}
            </h1>

            <p class="text-sm text-dark-500 dark:text-dark-400">
                {{ __('Rename or archive this workspace.') }}
            </p>
        </div>

        <form wire:submit="renameWorkspace" class="space-y-6">
            <x-input
                wire:model="name"
                :label="__('Name')"
                type="text"
                required
                maxlength="120"
                data-test="workspace-name-input"
            />

            <x-button
                submit
                wire:loading.attr="disabled"
                wire:target="renameWorkspace"
                data-test="rename-workspace-button"
                :text="__('Save')"
            />
        </form>
    </div>

    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-1">
            <h2 class="text-lg font-medium tracking-tight text-dark-800 dark:text-white">
                {{ __('Archive workspace') }}
            </h2>

            <p class="text-sm text-dark-500 dark:text-dark-400">
                {{ __('An archived workspace disappears from the switcher. Its data is kept.') }}
            </p>
        </div>

        @error('workspace')
            <p class="text-sm text-red-600 dark:text-red-400" data-test="archive-error">{{ $message }}</p>
        @enderror

        <div>
            <x-button
                color="red"
                data-test="archive-workspace-button"
                :text="__('Archive workspace')"
                x-on:click="$tsui.open.modal('confirm-workspace-archive')"
            />
        </div>
    </div>

    <x-modal id="confirm-workspace-archive" size="lg" wire="showArchiveModal">
        <div class="space-y-6">
            <div>
                <h2 class="text-lg font-medium tracking-tight text-dark-800 dark:text-white">
                    {{ __('Archive this workspace?') }}
                </h2>

                <p class="text-sm text-dark-500 dark:text-dark-400">
                    {{ __('Members will no longer be able to open it.') }}
                </p>
            </div>

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <x-button flat :text="__('Cancel')" x-on:click="$tsui.close.modal('confirm-workspace-archive')" />

                <x-button
                    color="red"
                    wire:click="archiveWorkspace"
                    x-on:click="$tsui.close.modal('confirm-workspace-archive')"
                    wire:loading.attr="disabled"
                    wire:target="archiveWorkspace"
                    data-test="confirm-archive-workspace-button"
                    :text="__('Archive workspace')"
                />
            </div>
        </div>
    </x-modal>
</section>
