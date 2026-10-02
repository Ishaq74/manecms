<x-layouts::auth
    :title="__('Confirm password')"
    :heading="__('Confirm password')"
    :description="__('This is a secure area of the application. Please confirm your password before continuing.')"
    :status="session('status')"
>
    <x-passkey-verify
        options-route="passkey.confirm-options"
        submit-route="passkey.confirm"
        :label="__('Confirm with passkey')"
        :loading-label="__('Confirming...')"
        :separator="__('Or confirm with password')"
    />

    <x-mane::form method="POST" action="{{ route('password.confirm.store') }}" :dirty-notice="false">
        @csrf

        <x-mane::password name="password" :label="__('Password')" required autofocus autocomplete="current-password" />

        <x-mane::button type="submit" block data-test="confirm-password-button" :text="__('Confirm')" />
    </x-mane::form>
</x-layouts::auth>
