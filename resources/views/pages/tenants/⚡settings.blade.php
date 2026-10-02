<?php

use App\Domain\Tenancy\Actions\RenameTenant;
use App\Domain\Tenancy\Context\TenantContext;
use App\Livewire\Concerns\HasFormContract;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::sidebar')] #[Title('Space settings')] class extends Component {
    use HasFormContract;
    public string $name = '';

    public function mount(TenantContext $context): void
    {
        $this->authorize('update', $context->tenant());

        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $this->name = app(TenantContext::class)->tenant()->name;
    }

    /**
     * Rename the current tenant.
     */
    public function renameTenant(RenameTenant $renameTenant): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $renameTenant($user, $this->name);

        $this->formSucceeded(__('Space renamed.'));
    }
}; ?>

<section class="mx-auto flex w-full max-w-2xl flex-col gap-6">
    <x-mane::page-header :title="__('Space settings')" :description="__('The space groups your workspaces, members and data.')" />

    <x-mane::card>
        <x-mane::form wire:submit="renameTenant">
            <x-mane::input wire:model="name" :label="__('Name')" type="text" required maxlength="120" data-test="tenant-name" />

            <x-slot:actions>
                <x-mane::button variant="ghost" wire:click="resetForm" :text="__('Discard changes')" data-test="reset-tenant-form" />
                <x-mane::button type="submit" loading="renameTenant" data-test="rename-tenant-button" :text="__('Save')" />
            </x-slot:actions>
        </x-mane::form>
    </x-mane::card>
</section>
