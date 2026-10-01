<x-layouts::auth :title="__('Two-factor authentication')">
    <div class="flex flex-col gap-6">
        <div
            class="relative w-full h-auto"
            x-cloak
            x-data="{
                showRecoveryInput: @js($errors->has('recovery_code')),
                code: '',
                recovery_code: '',
                focusOtp() {
                    this.$nextTick(() => this.$refs.otp?.querySelector('input')?.focus());
                },
                init() {
                    if (! this.showRecoveryInput) {
                        this.focusOtp();
                    }
                },
                toggleInput() {
                    this.showRecoveryInput = !this.showRecoveryInput;

                    this.code = '';
                    this.recovery_code = '';

                    $nextTick(() => {
                        this.showRecoveryInput
                            ? this.$refs.recovery_code?.focus()
                            : this.focusOtp();
                    });
                },
            }"
        >
            <div x-show="!showRecoveryInput">
                <x-auth-header
                    :title="__('Authentication code')"
                    :description="__('Enter the authentication code provided by your authenticator application.')"
                />
            </div>

            <div x-show="showRecoveryInput">
                <x-auth-header
                    :title="__('Recovery code')"
                    :description="__('Please confirm access to your account by entering one of your emergency recovery codes.')"
                />
            </div>

            <form method="POST" action="{{ route('two-factor.login.store') }}">
                @csrf

                <div class="space-y-5 text-center">
                    <div x-show="!showRecoveryInput">
                        <div class="flex items-center justify-center my-5" x-ref="otp">
                            <input type="hidden" name="code" x-model="code" />

                            <template x-for="index in 6" :key="index">
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="1"
                                    :autocomplete="index === 1 ? 'one-time-code' : 'off'"
                                    aria-label="{{ __('Authentication code digit') }} {{ index }}"
                                    class="h-12 w-12 rounded-lg border border-dark-300 text-center text-lg font-semibold text-dark-800 focus:border-dark-500 focus:ring-2 focus:ring-dark-500 focus:outline-hidden dark:border-dark-700 dark:bg-dark-900 dark:text-white"
                                    x-model="code[ index - 1 ]"
                                    x-on:input="$el.value = $el.value.replace(/\D/g, '').slice(-1)"
                                    x-on:keydown.backspace.prevent="if (! $el.value && index > 1) { $root.querySelectorAll('input')[ index - 2 ].focus() }"
                                />
                            </template>
                        </div>
                    </div>

                    <div x-show="showRecoveryInput">
                        <div class="my-5">
                            <x-input
                                type="text"
                                name="recovery_code"
                                x-ref="recovery_code"
                                x-bind:required="showRecoveryInput"
                                autocomplete="one-time-code"
                                x-model="recovery_code"
                            />
                        </div>

                        @error('recovery_code')
                            <p class="text-sm text-primary-600 dark:text-primary-400">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <x-button submit block :text="__('Continue')" />
                </div>

                <div class="mt-5 space-x-0.5 text-sm leading-5 text-center">
                    <span class="opacity-50">{{ __('or you can') }}</span>
                    <div class="inline font-medium underline cursor-pointer opacity-80">
                        <span x-show="!showRecoveryInput" @click="toggleInput()">{{ __('login using a recovery code') }}</span>
                        <span x-show="showRecoveryInput" @click="toggleInput()">{{ __('login using an authentication code') }}</span>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layouts::auth>
