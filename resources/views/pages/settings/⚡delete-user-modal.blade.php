<?php

use App\Concerns\PasswordValidationRules;
use App\Domain\Identity\Actions\DeleteUser;
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
    public function deleteUser(Logout $logout, DeleteUser $deleteUser): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        $deleteUser(Auth::user(), $logout);

        $this->redirect('/', navigate: true);
    }
}; ?>

<x-mane::modal id="confirm-user-deletion" size="lg" wire="showDeletionModal">
    <x-mane::form wire:submit="deleteUser" :dirty-notice="false">
        <x-mane::section-header
            :title="__('Are you sure you want to delete your account?')"
            :description="__('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.')"
        />

        <x-mane::password wire:model="password" :label="__('Password')" autocomplete="current-password" />

        <x-slot:actions>
            <x-mane::button variant="ghost" :text="__('Cancel')" x-on:click="$tsui.close.modal('confirm-user-deletion')" />
            <x-mane::button variant="danger" type="submit" loading="deleteUser" data-test="confirm-delete-user-button" :text="__('Delete account')" />
        </x-slot:actions>
    </x-mane::form>
</x-mane::modal>
