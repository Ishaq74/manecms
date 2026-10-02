<?php

use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public bool $requiresConfirmation;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    public bool $showVerificationStep = false;

    public bool $setupComplete = false;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    /**
     * Mount the component.
     */
    public function mount(bool $requiresConfirmation): void
    {
        $this->requiresConfirmation = $requiresConfirmation;
    }

    #[On('start-two-factor-setup')]
    public function startTwoFactorSetup(): void
    {
        $enableTwoFactorAuthentication = app(EnableTwoFactorAuthentication::class);
        $enableTwoFactorAuthentication(auth()->user());

        $this->loadSetupData();
    }

    /**
     * Load the two-factor authentication setup data for the user.
     */
    private function loadSetupData(): void
    {
        $user = auth()->user()?->fresh();

        try {
            if (! $user || ! $user->two_factor_secret) {
                throw new Exception('Two-factor setup secret is not available.');
            }

            $this->qrCodeSvg = $user->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (Exception) {
            $this->addError('setupData', 'Failed to fetch setup data.');

            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    /**
     * Show the two-factor verification step if necessary.
     */
    public function showVerificationIfNecessary(): void
    {
        if ($this->requiresConfirmation) {
            $this->showVerificationStep = true;

            $this->resetErrorBag();

            return;
        }

        $this->closeModal();
        $this->dispatch('two-factor-enabled');
    }

    /**
     * Confirm two-factor authentication for the user.
     */
    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validate();

        $confirmTwoFactorAuthentication(auth()->user(), $this->code);

        $this->setupComplete = true;

        $this->closeModal();

        $this->dispatch('two-factor-enabled');
    }

    /**
     * Reset two-factor verification state.
     */
    public function resetVerification(): void
    {
        $this->reset('code', 'showVerificationStep');

        $this->resetErrorBag();
    }

    /**
     * Close the two-factor authentication modal.
     */
    public function closeModal(): void
    {
        $this->reset(
            'code',
            'manualSetupKey',
            'qrCodeSvg',
            'showVerificationStep',
            'setupComplete',
        );

        $this->resetErrorBag();
    }

    /**
     * Get the current modal configuration state.
     */
    #[Computed]
    public function modalConfig(): array
    {
        if ($this->setupComplete) {
            return [
                'title' => __('Two-factor authentication enabled'),
                'description' => __('Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.'),
                'buttonText' => __('Close'),
            ];
        }

        if ($this->showVerificationStep) {
            return [
                'title' => __('Verify authentication code'),
                'description' => __('Enter the 6-digit code from your authenticator app.'),
                'buttonText' => __('Continue'),
            ];
        }

        return [
            'title' => __('Enable two-factor authentication'),
            'description' => __('To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app.'),
            'buttonText' => __('Continue'),
        ];
    }
}; ?>

<x-mane::modal id="two-factor-setup-modal" size="md" x-on:close="$wire.closeModal()">
    <div class="flex flex-col gap-6">
        <div class="flex flex-col items-center gap-4 text-center">
            <span class="flex size-12 items-center justify-center rounded-full bg-brand-subtle text-brand">
                <x-mane::icon name="qr-code" class="size-6" />
            </span>

            <x-mane::section-header class="items-center" :title="$this->modalConfig['title']" :description="$this->modalConfig['description']" />
        </div>

        @if ($showVerificationStep)
            <div class="flex justify-center" x-data x-init="$nextTick(() => $el.querySelector('input:not([type=hidden])')?.focus())">
                <x-mane::pin wire:model="code" :length="6" :label="__('OTP Code')" />
            </div>

            <div class="flex gap-3">
                <x-mane::button variant="secondary" class="flex-1" wire:click="resetVerification" :text="__('Back')" />
                <x-mane::button class="flex-1" wire:click="confirmTwoFactor" loading="confirmTwoFactor" x-bind:disabled="$wire.code.length < 6" :text="__('Confirm')" />
            </div>
        @else
            @error('setupData')
                <x-mane::alert tone="danger" :title="$message" />
            @enderror

            <div class="flex justify-center">
                <div class="relative flex aspect-square w-64 items-center justify-center overflow-hidden rounded-surface border border-line">
                    @empty($qrCodeSvg)
                        <x-mane::skeleton :lines="1" class="absolute inset-0 [&>span]:h-full" />
                    @else
                        <div class="bg-white p-3 rounded dark:invert dark:brightness-150">
                            {!! $qrCodeSvg !!}
                        </div>
                    @endempty
                </div>
            </div>

            <x-mane::button
                block
                :disabled="$errors->has('setupData')"
                wire:click="showVerificationIfNecessary"
                loading="showVerificationIfNecessary"
                :text="$this->modalConfig['buttonText']"
            />

            <x-mane::divider :label="__('or, enter the code manually')" />

            <div
                class="flex items-end gap-2"
                x-data="{
                    copied: false,
                    async copy() {
                        try {
                            await navigator.clipboard.writeText(@js($manualSetupKey));
                            this.copied = true;
                            setTimeout(() => this.copied = false, 1500);
                        } catch (e) {
                            console.warn('Could not copy to clipboard');
                        }
                    },
                }"
            >
                @empty($manualSetupKey)
                    <x-mane::loading-state class="w-full justify-center" />
                @else
                    <div class="flex-1">
                        <x-mane::input readonly :value="$manualSetupKey" :label="__('Manual setup key')" class="font-mono" />
                    </div>

                    <x-mane::icon-button icon="clipboard-document" variant="secondary" :label="__('Copy')" x-on:click="copy()" />

                    <span class="sr-only" role="status" x-text="copied ? @js(__('Copied')) : ''"></span>
                @endempty
            </div>
        @endif
    </div>
</x-mane::modal>
