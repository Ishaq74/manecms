@assets
@vite('resources/js/passkeys.js')
@endassets

<div
    x-data="{
        supported: false,
        showForm: false,
        name: '',
        loading: false,
        error: null,
        updateSupport() {
            this.supported = Boolean(window.Passkeys?.isSupported());
        },
        getDefaultPasskeyName() {
            const ua = navigator.userAgent;

            const browser = [
                { pattern: /Edg|Edge/, name: 'Edge' },
                { pattern: /OPR|Opera|OPiOS/, name: 'Opera' },
                { pattern: /Firefox|FxiOS/, name: 'Firefox' },
                { pattern: /Chrome|CriOS/, name: 'Chrome' },
                { pattern: /Safari/, name: 'Safari' },
            ].find(({ pattern }) => pattern.test(ua))?.name;

            const os = [
                { pattern: /iPhone/, name: 'iPhone' },
                { pattern: /iPad|Macintosh(?=.*Mobile)/, name: 'iPad' },
                { pattern: /Android/, name: 'Android' },
                { pattern: /Mac/, name: 'Mac' },
                { pattern: /Windows/, name: 'Windows' },
            ].find(({ pattern }) => pattern.test(ua))?.name;

            return [browser, os].filter(Boolean).join(' on ') || '';
        },
        init() {
            this.name = this.getDefaultPasskeyName();
            this.updateSupport();

            window.addEventListener('passkeys:ready', () => this.updateSupport(), { once: true });
        },
        async register() {
            if (!this.name.trim()) return;

            this.loading = true;
            this.error = null;

            try {
                await window.Passkeys.register({ name: this.name });
                this.name = '';
                this.showForm = false;
                await $wire.loadPasskeys();
            } catch (e) {
                if (e.constructor?.name !== 'UserCancelledError') {
                    this.error = e.message;
                }
            } finally {
                this.loading = false;
            }
        },
        cancel() {
            this.showForm = false;
            this.name = '';
            this.error = null;
        },
    }"
>
    <template x-if="! supported">
        <x-mane::alert tone="info" :text="__('Passkeys are not supported in this browser.')" />
    </template>

    <template x-if="supported && ! showForm">
        <div>
            <x-mane::button variant="secondary" icon="plus" x-on:click="showForm = true" :text="__('Add passkey')" />
        </div>
    </template>

    <template x-if="supported && showForm">
        <div class="flex flex-col gap-4 rounded-surface border border-line bg-surface-sunken p-4">
            <x-mane::input
                :label="__('Passkey name')"
                :hint="__('Give this passkey a name to help you identify it later.')"
                x-model="name"
                placeholder="{{ __('e.g., MacBook Pro, iPhone') }}"
                x-on:keydown.enter.prevent="register()"
                x-ref="passkeyNameInput"
                x-init="$nextTick(() => $refs.passkeyNameInput?.focus())"
            />

            <p x-show="error" x-text="error" x-cloak role="alert" class="text-sm font-medium text-danger"></p>

            <div class="flex gap-2">
                <x-mane::button icon="finger-print" x-on:click="register()" x-bind:disabled="loading || ! name.trim()">
                    <span x-show="! loading">{{ __('Register passkey') }}</span>
                    <span x-show="loading" x-cloak>{{ __('Registering...') }}</span>
                </x-mane::button>

                <x-mane::button variant="ghost" x-on:click="cancel()" :text="__('Cancel')" />
            </div>
        </div>
    </template>
</div>
