<?php

use App\Concerns\PasswordValidationRules;
use TallStackUi\Traits\Interactions;
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
    use Interactions;
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
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->toast()->success(__('Password updated.'))->send();
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

<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('Security settings') }}</h2>

    <x-pages::settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
            <x-password
                wire:model="current_password"
                :label="__('Current password')"
                required
                autocomplete="current-password"
            />

            <x-password
                wire:model="password"
                :label="__('New password')"
                required
                autocomplete="new-password"
                :rules="true"
            />

            <x-password
                wire:model="password_confirmation"
                :label="__('Confirm password')"
                required
                autocomplete="new-password"
                :rules="true"
            />

            <div class="flex items-center gap-4">
                <x-button submit data-test="update-password-button" :text="__('Save')" />
            </div>
        </form>

        @if ($canManageTwoFactor)
            <section class="mt-12">
                <h2 class="text-lg font-medium tracking-tight text-zinc-800 dark:text-white">
                    {{ __('Two-factor authentication') }}
                </h2>

                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Manage your two-factor authentication settings') }}
                </p>

                <div class="flex flex-col w-full mx-auto space-y-6 text-sm" wire:cloak>
                    @if ($twoFactorEnabled)
                        <div class="space-y-4">
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ __('You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
                            </p>

                            <div class="flex justify-start">
                                <x-button color="red" wire:click="disable" :text="__('Disable 2FA')" />
                            </div>

                            <livewire:pages::settings.two-factor.recovery-codes :$requiresConfirmation />
                        </div>
                    @else
                        <div class="space-y-4">
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
                            </p>

                            <x-button
                                wire:click="$dispatch('start-two-factor-setup')"
                                x-on:click="$tsui.open.modal('two-factor-setup-modal')"
                                :text="__('Enable 2FA')"
                            />

                            <livewire:pages::settings.two-factor-setup-modal :requires-confirmation="$requiresConfirmation" />
                        </div>
                    @endif
                </div>
            </section>
        @endif

        @if ($canManagePasskeys)
            <section class="mt-12">
                <h2 class="text-lg font-medium tracking-tight text-zinc-800 dark:text-white">
                    {{ __('Passkeys') }}
                </h2>

                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Manage your passkeys for passwordless sign-in') }}
                </p>

                <div class="mt-6 flex flex-col w-full mx-auto space-y-6 text-sm" wire:cloak>
                    <div class="border rounded-lg border-zinc-200 dark:border-zinc-700 overflow-hidden">
                        @forelse ($passkeys as $passkey)
                            <div class="flex items-center justify-between p-4 {{ ! $loop->last ? 'border-b border-zinc-200 dark:border-zinc-700' : '' }}">
                                <div class="flex items-center gap-4">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                                        <svg class="size-5 text-zinc-500 dark:text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-5.879a1.5 1.5 0 0 1 .4-.925l6.404-5.977a3 3 0 0 1 6.946 3.181Z" />
                                        </svg>
                                    </div>
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2.5">
                                            <p class="font-medium tracking-tight">{{ $passkey['name'] }}</p>
                                            @if ($passkey['authenticator'])
                                                <x-badge sm>{{ $passkey['authenticator'] }}</x-badge>
                                            @endif
                                        </div>
                                        <p class="text-zinc-500 dark:text-zinc-400 text-xs">
                                            {{ __('Added :time', ['time' => $passkey['created_at_diff']]) }}
                                            @if ($passkey['last_used_at_diff'])
                                                <span class="opacity-50 mx-1">/</span>
                                                {{ __('Last used :time', ['time' => $passkey['last_used_at_diff']]) }}
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <x-button
                                    flat
                                    sm
                                    color="red"
                                    wire:click="confirmDelete({{ $passkey['id'] }})"
                                    aria-label="{{ __('Remove passkey') }}"
                                    data-test="remove-passkey-button"
                                >
                                    <x-icon name="trash" />
                                </x-button>
                            </div>
                        @empty
                            <div class="p-8 text-center">
                                <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                                    <svg class="size-7 text-zinc-400 dark:text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-5.879a1.5 1.5 0 0 1 .4-.925l6.404-5.977a3 3 0 0 1 6.946 3.181Z" />
                                    </svg>
                                </div>
                                <p class="font-medium">{{ __('No passkeys yet') }}</p>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ __('Add a passkey to sign in without a password') }}
                                </p>
                            </div>
                        @endforelse
                    </div>

                    <x-passkey-registration />
                </div>
            </section>
        @endif
    </x-pages::settings.layout>

    <x-modal id="delete-passkey-modal" size="md" wire="showDeleteModal">
        <div class="space-y-6">
            <div class="space-y-2">
                <h2 class="text-lg font-medium tracking-tight text-zinc-800 dark:text-white">
                    {{ __('Remove passkey') }}
                </h2>

                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Are you sure you want to remove the passkey ":name"? You will no longer be able to use it to sign in.', ['name' => $deletingPasskeyName]) }}
                </p>
            </div>

            <div class="flex gap-3 justify-end">
                <x-button outline wire:click="closeDeleteModal" :text="__('Cancel')" />
                <x-button color="red" wire:click="deletePasskey" :text="__('Remove passkey')" />
            </div>
        </div>
    </x-modal>
</section>
