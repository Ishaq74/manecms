<?php

use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public array $recoveryCodes = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->loadRecoveryCodes();
    }

    /**
     * Generate new recovery codes for the user.
     */
    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
    {
        $generateNewRecoveryCodes(auth()->user());

        $this->loadRecoveryCodes();
    }

    /**
     * Load the recovery codes for the user.
     */
    private function loadRecoveryCodes(): void
    {
        $user = auth()->user();

        if ($user->hasEnabledTwoFactorAuthentication() && $user->two_factor_recovery_codes) {
            try {
                $this->recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
            } catch (Exception) {
                $this->addError('recoveryCodes', 'Failed to load recovery codes');

                $this->recoveryCodes = [];
            }
        }
    }
}; ?>

<div class="flex flex-col gap-4 rounded-surface border border-line p-4" wire:cloak x-data="{ showRecoveryCodes: false }">
    <x-mane::section-header :level="3" :title="__('2FA recovery codes')" :description="__('Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.')" />

    <div class="flex flex-wrap items-center gap-3">
        <span x-show="! showRecoveryCodes">
            <x-mane::button
                variant="secondary"
                icon="eye"
                x-on:click="showRecoveryCodes = true"
                aria-expanded="false"
                aria-controls="recovery-codes-section"
                :text="__('View recovery codes')"
            />
        </span>

        <span x-show="showRecoveryCodes">
            <x-mane::button
                variant="secondary"
                icon="eye-slash"
                x-on:click="showRecoveryCodes = false"
                aria-expanded="true"
                aria-controls="recovery-codes-section"
                :text="__('Hide recovery codes')"
            />
        </span>

        @if (filled($recoveryCodes))
            <x-mane::button
                icon="arrow-path"
                variant="ghost"
                wire:click="regenerateRecoveryCodes"
                loading="regenerateRecoveryCodes"
                :text="__('Regenerate codes')"
            />
        @endif
    </div>

    <div x-show="showRecoveryCodes" x-transition id="recovery-codes-section" class="flex flex-col gap-3">
        @error('recoveryCodes')
            <x-mane::alert tone="danger" :title="$message" />
        @enderror

        @if (filled($recoveryCodes))
            <ul class="grid gap-1 rounded-surface bg-surface-sunken p-4 font-mono text-sm sm:grid-cols-2" aria-label="{{ __('Recovery codes') }}">
                @foreach ($recoveryCodes as $code)
                    <li class="select-text" wire:key="recovery-code-{{ $loop->index }}" wire:loading.class="opacity-50">{{ $code }}</li>
                @endforeach
            </ul>

            <p class="text-xs text-fg-muted">
                {{ __('Each recovery code can be used once to access your account and will be removed after use. If you need more, click Regenerate codes above.') }}
            </p>
        @endif
    </div>
</div>
