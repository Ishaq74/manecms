<x-layouts::auth :title="__('Two-factor authentication')">
    <div
        class="flex flex-col gap-6"
        x-cloak
        x-data="{
            showRecoveryInput: @js($errors->has('recovery_code')),
            focusOtp() {
                this.$nextTick(() => this.$refs.otp?.querySelector('input:not([type=hidden])')?.focus());
            },
            init() {
                if (! this.showRecoveryInput) {
                    this.focusOtp();
                }
            },
            toggleInput() {
                this.showRecoveryInput = ! this.showRecoveryInput;

                this.$nextTick(() => this.showRecoveryInput ? this.$refs.recovery_code?.focus() : this.focusOtp());
            },
        }"
    >
        <div x-show="! showRecoveryInput">
            <x-mane::page-header
                align="center"
                :title="__('Authentication code')"
                :description="__('Enter the authentication code provided by your authenticator application.')"
            />
        </div>

        <div x-show="showRecoveryInput">
            <x-mane::page-header
                align="center"
                :title="__('Recovery code')"
                :description="__('Please confirm access to your account by entering one of your emergency recovery codes.')"
            />
        </div>

        <x-mane::form method="POST" action="{{ route('two-factor.login.store') }}" :dirty-notice="false">
            @csrf

            <div x-show="! showRecoveryInput">
                <x-mane::otp-input name="code" :label="__('Authentication code')" x-ref="otp" />
            </div>

            <div x-show="showRecoveryInput">
                <x-mane::input
                    type="text"
                    name="recovery_code"
                    :label="__('Recovery code')"
                    x-ref="recovery_code"
                    x-bind:required="showRecoveryInput"
                    x-bind:disabled="! showRecoveryInput"
                    autocomplete="one-time-code"
                />
            </div>

            <x-mane::button type="submit" block :text="__('Continue')" />
        </x-mane::form>

        <x-mane::divider :label="__('or you can')" />

        <div class="flex justify-center">
            <span x-show="! showRecoveryInput">
                <x-mane::button variant="ghost" size="sm" x-on:click="toggleInput()" :text="__('login using a recovery code')" />
            </span>

            <span x-show="showRecoveryInput">
                <x-mane::button variant="ghost" size="sm" x-on:click="toggleInput()" :text="__('login using an authentication code')" />
            </span>
        </div>
    </div>
</x-layouts::auth>
