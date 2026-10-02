<?php

use App\Domain\Tenancy\Actions\ArchiveWorkspace;
use App\Domain\Tenancy\Actions\RenameWorkspace;
use App\Domain\Tenancy\Context\TenantContext;
use App\Livewire\Concerns\HasFormContract;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::sidebar')] #[Title('Workspace settings')] class extends Component {
    use HasFormContract;
    public string $name = '';

    public bool $showArchiveModal = false;

    public function mount(TenantContext $context): void
    {
        $this->authorize('update', $context->workspace());

        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $this->name = app(TenantContext::class)->workspace()->name;
    }

    /**
     * Rename the current workspace.
     */
    public function renameWorkspace(RenameWorkspace $renameWorkspace, TenantContext $context): void
    {
        $renameWorkspace($this->user(), $context->workspace()->id, $this->name);

        $this->formSucceeded(__('Workspace renamed.'));
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

<section class="mx-auto flex w-full max-w-2xl flex-col gap-6">
    <x-mane::page-header :title="__('Workspace settings')" :description="__('Rename or archive this workspace.')" />

    <x-mane::card>
        <x-mane::form wire:submit="renameWorkspace">
            <x-mane::input wire:model="name" :label="__('Name')" type="text" required maxlength="120" data-test="workspace-name-input" />

            <x-slot:actions>
                <x-mane::button variant="ghost" wire:click="resetForm" :text="__('Discard changes')" data-test="reset-workspace-form" />
                <x-mane::button type="submit" loading="renameWorkspace" data-test="rename-workspace-button" :text="__('Save')" />
            </x-slot:actions>
        </x-mane::form>
    </x-mane::card>

    <x-mane::card>
        <div class="flex flex-col items-start gap-4">
            <x-mane::section-header :level="3" :title="__('Archive workspace')" :description="__('An archived workspace disappears from the switcher. Its data is kept.')" />

            @error('workspace')
                <x-mane::alert tone="danger" :text="$message" data-test="archive-error" />
            @enderror

            <x-mane::button
                variant="danger"
                icon="archive-box"
                data-test="archive-workspace-button"
                :text="__('Archive workspace')"
                x-on:click="$tsui.open.modal('confirm-workspace-archive')"
            />
        </div>
    </x-mane::card>

    <x-mane::modal id="confirm-workspace-archive" size="lg" wire="showArchiveModal">
        <div class="flex flex-col gap-6">
            <x-mane::section-header :title="__('Archive this workspace?')" :description="__('Members will no longer be able to open it.')" />

            <div class="flex justify-end gap-2">
                <x-mane::button variant="ghost" :text="__('Cancel')" x-on:click="$tsui.close.modal('confirm-workspace-archive')" />

                <x-mane::button
                    variant="danger"
                    wire:click="archiveWorkspace"
                    x-on:click="$tsui.close.modal('confirm-workspace-archive')"
                    loading="archiveWorkspace"
                    data-test="confirm-archive-workspace-button"
                    :text="__('Archive workspace')"
                />
            </div>
        </div>
    </x-mane::modal>
</section>
