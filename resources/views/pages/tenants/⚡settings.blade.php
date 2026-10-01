<?php

use App\Domain\Tenancy\Actions\RenameTenant;
use App\Domain\Tenancy\Context\TenantContext;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new #[Layout('layouts::sidebar')] #[Title('Space settings')] class extends Component {
    use Interactions;

    public string $name = '';

    public function mount(TenantContext $context): void
    {
        $this->authorize('update', $context->tenant());

        $this->name = $context->tenant()->name;
    }

    /**
     * Rename the current tenant.
     */
    public function renameTenant(RenameTenant $renameTenant): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $renameTenant($user, $this->name);

        $this->toast()->success(__('Space renamed.'))->send();
    }
}; ?>

<section class="mx-auto flex w-full max-w-2xl flex-col gap-6">
    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-semibold tracking-tight text-dark-900 dark:text-white">
            {{ __('Space settings') }}
        </h1>

        <p class="text-sm text-dark-500 dark:text-dark-400">
            {{ __('The space groups your workspaces, members and data.') }}
        </p>
    </div>

    <form wire:submit="renameTenant" class="space-y-6">
        <x-input
            wire:model="name"
            :label="__('Name')"
            type="text"
            required
            maxlength="120"
            data-test="tenant-name"
        />

        <x-button
            submit
            wire:loading.attr="disabled"
            wire:target="renameTenant"
            data-test="rename-tenant-button"
            :text="__('Save')"
        />
    </form>
</section>
