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

<x-modal
    id="two-factor-setup-modal"
    size="md"
    x-on:close="$wire.closeModal()"
>
        <div class="space-y-6">
            <div class="flex flex-col items-center space-y-4">
                <div class="p-0.5 w-auto rounded-full border border-dark-200 dark:border-dark-600 bg-white dark:bg-dark-800 shadow-sm">
                    <div class="p-2.5 rounded-full border border-dark-200 dark:border-dark-600 overflow-hidden bg-dark-100 dark:bg-dark-200 relative">
                        <div class="flex items-stretch absolute inset-0 w-full h-full divide-x [&>div]:flex-1 divide-dark-200 dark:divide-dark-300 justify-around opacity-50">
                            @for ($i = 1; $i <= 5; $i++)
                                <div></div>
                            @endfor
                        </div>

                        <div class="flex flex-col items-stretch absolute w-full h-full divide-y [&>div]:flex-1 inset-0 divide-dark-200 dark:divide-dark-300 justify-around opacity-50">
                            @for ($i = 1; $i <= 5; $i++)
                                <div></div>
                            @endfor
                        </div>

                        <svg class="relative z-20 size-6 dark:text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                        </svg>
                    </div>
                </div>

                <div class="space-y-2 text-center">
                    <h2 class="text-lg font-medium tracking-tight text-dark-800 dark:text-white">
                        {{ $this->modalConfig['title'] }}
                    </h2>

                    <p class="text-sm text-dark-500 dark:text-dark-400">
                        {{ $this->modalConfig['description'] }}
                    </p>
                </div>
            </div>

            @if ($showVerificationStep)
                <div class="space-y-6">
                    <div
                        class="flex flex-col items-center space-y-3 justify-center"
                        x-data
                        x-init="$nextTick(() => $el.querySelector('input')?.focus())"
                    >
                        <x-pin
                            wire:model="code"
                            :length="6"
                            :label="__('OTP Code')"
                            class="mx-auto"
                            sr-only
                        />
                    </div>

                    <div class="flex items-center space-x-3">
                        <x-button
                            outline
                            class="flex-1"
                            wire:click="resetVerification"
                            :text="__('Back')"
                        />

                        <x-button
                            class="flex-1"
                            wire:click="confirmTwoFactor"
                            x-bind:disabled="$wire.code.length < 6"
                            :text="__('Confirm')"
                        />
                    </div>
                </div>
            @else
                @error('setupData')
                    <x-alert color="red" icon="x-circle" :title="$message" />
                @enderror

                <div class="flex justify-center">
                    <div class="relative w-64 overflow-hidden border rounded-lg border-dark-200 dark:border-dark-700 aspect-square">
                        @empty($qrCodeSvg)
                            <div class="absolute inset-0 flex items-center justify-center bg-white dark:bg-dark-700 animate-pulse">
                                <x-spinner />
                            </div>
                        @else
                            <div x-data class="flex items-center justify-center h-full p-4">
                                <div class="bg-white p-3 rounded dark:invert dark:brightness-150">
                                    {!! $qrCodeSvg !!}
                                </div>
                            </div>
                        @endempty
                    </div>
                </div>

                <div>
                    <x-button
                        block
                        :disabled="$errors->has('setupData')"
                        wire:click="showVerificationIfNecessary"
                        :text="$this->modalConfig['buttonText']"
                    />
                </div>

                <div class="space-y-4">
                    <div class="relative flex items-center justify-center w-full">
                        <div class="absolute inset-0 w-full h-px top-1/2 bg-dark-200 dark:bg-dark-600"></div>
                        <span class="relative px-2 text-sm bg-white dark:bg-dark-800 text-dark-600 dark:text-dark-400">
                            {{ __('or, enter the code manually') }}
                        </span>
                    </div>

                    <div
                        class="flex items-center space-x-2"
                        x-data="{
                            copied: false,
                            async copy() {
                                try {
                                    await navigator.clipboard.writeText('{{ $manualSetupKey }}');
                                    this.copied = true;
                                    setTimeout(() => this.copied = false, 1500);
                                } catch (e) {
                                    console.warn('Could not copy to clipboard');
                                }
                            }
                        }"
                    >
                        <div class="flex items-stretch w-full border rounded-xl dark:border-dark-700">
                            @empty($manualSetupKey)
                                <div class="flex items-center justify-center w-full p-3 bg-dark-100 dark:bg-dark-700">
                                    <x-spinner xs />
                                </div>
                            @else
                                <input
                                    type="text"
                                    readonly
                                    value="{{ $manualSetupKey }}"
                                    aria-label="{{ __('Manual setup key') }}"
                                    class="w-full p-3 bg-transparent outline-none text-dark-900 dark:text-dark-100"
                                />

                                <x-button.circle
                                    flat
                                    xs
                                    @click="copy()"
                                    class="border-s border-l-0 border-dark-200 dark:border-dark-600"
                                >
                                    <x-icon name="clipboard-document" x-show="!copied" />
                                    <x-icon name="check" x-show="copied" class="text-secondary-600" />
                                </x-button.circle>
                            @endempty
                        </div>
                    </div>
                </div>
            @endif
        </div>
</x-modal>
