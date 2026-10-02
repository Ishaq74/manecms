@props([
    'optionsRoute' => 'passkey.login-options',
    'submitRoute' => 'passkey.login',
    'label' => __('Sign in with a passkey'),
    'loadingLabel' => __('Authenticating...'),
    'separator' => __('Or continue with email'),
])

@assets
@vite('resources/js/passkeys.js')
@endassets

<div
    x-data="{
        supported: false,
        loading: false,
        error: null,
        updateSupport() {
            this.supported = Boolean(window.Passkeys?.isSupported());
        },
        init() {
            this.updateSupport();

            window.addEventListener('passkeys:ready', () => this.updateSupport(), { once: true });
        },
        async verify() {
            this.loading = true;
            this.error = null;
            try {
                const response = await window.Passkeys.verify({
                    routes: {
                        options: '{{ route($optionsRoute) }}',
                        submit: '{{ route($submitRoute) }}',
                    },
                });
                Livewire.navigate(response.redirect || '/dashboard');
            } catch (e) {
                if (e.constructor?.name !== 'UserCancelledError') {
                    this.error = e.message;
                }
            } finally {
                this.loading = false;
            }
        },
    }"
>
    <template x-if="supported">
        <div class="flex flex-col gap-6">
            <div class="flex flex-col gap-2">
                <x-mane::button variant="secondary" icon="finger-print" block x-on:click="verify()" x-bind:disabled="loading">
                    <span x-show="! loading">{{ $label }}</span>
                    <span x-show="loading" x-cloak>{{ $loadingLabel }}</span>
                </x-mane::button>

                <p x-show="error" x-text="error" x-cloak role="alert" class="text-center text-sm font-medium text-danger"></p>
            </div>

            <x-mane::divider :label="$separator" />
        </div>
    </template>
</div>
