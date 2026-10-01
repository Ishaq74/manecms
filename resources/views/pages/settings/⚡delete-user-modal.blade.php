<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public string $password = '';

    public bool $showDeletionModal = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->showDeletionModal = (bool) session('errors');
    }

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<x-modal id="confirm-user-deletion" size="lg" wire="showDeletionModal">
    <form method="POST" wire:submit="deleteUser" class="space-y-6">
        <div>
            <h2 class="text-lg font-medium tracking-tight text-dark-800 dark:text-white">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="text-sm text-dark-500 dark:text-dark-400">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>
        </div>

        <x-password wire:model="password" :label="__('Password')" />

        <div class="flex justify-end space-x-2 rtl:space-x-reverse">
            <x-button flat :text="__('Cancel')" x-on:click="$tsui.close.modal('confirm-user-deletion')" />

            <x-button color="red" submit data-test="confirm-delete-user-button" :text="__('Delete account')" />
        </div>
    </form>
</x-modal>
