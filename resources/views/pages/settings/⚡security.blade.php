<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Concerns\HasFormContract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Laravel\Passkeys\Actions\DeletePasskey;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

new #[Layout('layouts::sidebar')] #[Title('Security settings')] class extends Component {
    use HasFormContract;
    use PasswordValidationRules;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $canManageTwoFactor;

    public bool $twoFactorEnabled;

    public bool $requiresConfirmation;

    #[Locked]
    public bool $canManagePasskeys;

    #[Locked]
    public array $passkeys = [];

    public bool $showDeleteModal = false;

    #[Locked]
    public ?int $deletingPasskeyId = null;

    #[Locked]
    public string $deletingPasskeyName = '';

    /**
     * Mount the component.
     */
    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
                $disableTwoFactorAuthentication(auth()->user());
            }

            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        $this->canManagePasskeys = Features::canManagePasskeys();

        if ($this->canManagePasskeys) {
            $this->loadPasskeys();
        }
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->fillForm();

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->fillForm();
        $this->formSucceeded(__('Password updated.'));
    }

    /**
     * Password fields are never kept: resetting the form empties them.
     */
    protected function fillForm(): void
    {
        $this->reset('current_password', 'password', 'password_confirmation');
    }

    /**
     * Load the user's passkeys.
     */
    public function loadPasskeys(): void
    {
        $this->passkeys = auth()->user()->passkeys()
            ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
            ->latest()
            ->get()
            ->map(fn ($passkey) => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'authenticator' => $passkey->authenticator,
                'created_at_diff' => $passkey->created_at->diffForHumans(),
                'last_used_at_diff' => $passkey->last_used_at?->diffForHumans(),
            ])
            ->toArray();
    }

    /**
     * Show the delete confirmation modal.
     */
    public function confirmDelete(int $passkeyId): void
    {
        $passkey = auth()->user()->passkeys()->findOrFail($passkeyId);

        $this->deletingPasskeyId = $passkey->id;
        $this->deletingPasskeyName = $passkey->name;
        $this->showDeleteModal = true;
    }

    /**
     * Delete the passkey.
     */
    public function deletePasskey(DeletePasskey $deletePasskey): void
    {
        if (! $this->deletingPasskeyId) {
            return;
        }

        $passkey = auth()->user()->passkeys()->findOrFail($this->deletingPasskeyId);

        $deletePasskey(auth()->user(), $passkey);

        $this->closeDeleteModal();
        $this->loadPasskeys();
    }

    /**
     * Close the delete confirmation modal.
     */
    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingPasskeyId = null;
        $this->deletingPasskeyName = '';
    }

    /**
     * Handle the two-factor authentication enabled event.
     */
    #[On('two-factor-enabled')]
    public function onTwoFactorEnabled(): void
    {
        $this->twoFactorEnabled = true;
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());

        $this->twoFactorEnabled = false;
    }
}; ?>

<x-pages::settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
    <x-mane::form wire:submit="updatePassword" :dirty-notice="false">
        <x-mane::password wire:model="current_password" :label="__('Current password')" required autocomplete="current-password" />

        <x-mane::password wire:model="password" :label="__('New password')" required autocomplete="new-password" rules />

        <x-mane::password wire:model="password_confirmation" :label="__('Confirm password')" required autocomplete="new-password" />

        <x-slot:actions>
            <x-mane::button type="submit" loading="updatePassword" data-test="update-password-button" :text="__('Save')" />
        </x-slot:actions>
    </x-mane::form>

    @if ($canManageTwoFactor)
        <x-mane::card wire:cloak>
            <div class="flex flex-col gap-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <x-mane::section-header :level="3" :title="__('Two-factor authentication')" :description="__('Manage your two-factor authentication settings')" />

                    @if ($twoFactorEnabled)
                        <x-mane::status tone="success" :text="__('Enabled')" />
                    @else
                        <x-mane::status tone="muted" :text="__('Disabled')" />
                    @endif
                </div>

                @if ($twoFactorEnabled)
                    <p class="text-sm text-fg-muted">
                        {{ __('You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
                    </p>

                    <div>
                        <x-mane::button variant="danger" wire:click="disable" loading="disable" :text="__('Disable 2FA')" />
                    </div>

                    <livewire:pages::settings.two-factor.recovery-codes :$requiresConfirmation />
                @else
                    <p class="text-sm text-fg-muted">
                        {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
                    </p>

                    <div>
                        <x-mane::button
                            icon="shield-check"
                            wire:click="$dispatch('start-two-factor-setup')"
                            x-on:click="$tsui.open.modal('two-factor-setup-modal')"
                            :text="__('Enable 2FA')"
                        />
                    </div>

                    <livewire:pages::settings.two-factor-setup-modal :requires-confirmation="$requiresConfirmation" />
                @endif
            </div>
        </x-mane::card>
    @endif

    @if ($canManagePasskeys)
        <x-mane::card wire:cloak>
            <div class="flex flex-col gap-4">
                <x-mane::section-header :level="3" :title="__('Passkeys')" :description="__('Manage your passkeys for passwordless sign-in')" />

                @if ($passkeys === [])
                    <x-mane::empty-state kind="first-use" :title="__('No passkeys yet')" :description="__('Add a passkey to sign in without a password')" />
                @else
                    <ul class="divide-y divide-line rounded-surface border border-line">
                        @foreach ($passkeys as $passkey)
                            <li class="flex items-center justify-between gap-4 p-4" wire:key="passkey-{{ $passkey['id'] }}">
                                <div class="flex min-w-0 items-center gap-4">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-surface bg-surface-sunken text-fg-muted">
                                        <x-mane::icon name="key" class="size-5" />
                                    </span>

                                    <div class="flex min-w-0 flex-col gap-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="truncate font-medium text-fg">{{ $passkey['name'] }}</p>

                                            @if ($passkey['authenticator'])
                                                <x-mane::badge variant="muted" size="sm" :text="$passkey['authenticator']" />
                                            @endif
                                        </div>

                                        <p class="text-xs text-fg-muted">
                                            {{ __('Added :time', ['time' => $passkey['created_at_diff']]) }}
                                            @if ($passkey['last_used_at_diff'])
                                                · {{ __('Last used :time', ['time' => $passkey['last_used_at_diff']]) }}
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <x-mane::icon-button
                                    icon="trash"
                                    size="sm"
                                    :label="__('Remove passkey')"
                                    wire:click="confirmDelete({{ $passkey['id'] }})"
                                    data-test="remove-passkey-button"
                                />
                            </li>
                        @endforeach
                    </ul>
                @endif

                <x-passkey-registration />
            </div>
        </x-mane::card>
    @endif

    <x-mane::modal id="delete-passkey-modal" size="md" wire="showDeleteModal">
        <div class="flex flex-col gap-6">
            <x-mane::section-header :title="__('Remove passkey')">
                {{ __('Are you sure you want to remove the passkey ":name"? You will no longer be able to use it to sign in.', ['name' => $deletingPasskeyName]) }}
            </x-mane::section-header>

            <div class="flex justify-end gap-3">
                <x-mane::button variant="ghost" wire:click="closeDeleteModal" :text="__('Cancel')" />
                <x-mane::button variant="danger" wire:click="deletePasskey" loading="deletePasskey" :text="__('Remove passkey')" />
            </div>
        </div>
    </x-mane::modal>
</x-pages::settings.layout>
