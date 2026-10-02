<x-layouts::auth
    :title="__('Forgot password')"
    :heading="__('Forgot password')"
    :description="__('Enter your email to receive a password reset link')"
    :status="session('status')"
>
    <x-mane::form method="POST" action="{{ route('password.email') }}" :dirty-notice="false">
        @csrf

        <x-mane::input
            name="email"
            :label="__('Email address')"
            type="email"
            required
            autofocus
            autocomplete="email"
            placeholder="email@example.com"
        />

        <x-mane::button type="submit" block data-test="email-password-reset-link-button" :text="__('Email password reset link')" />
    </x-mane::form>

    <x-slot:footer>
        {{ __('Or, return to') }}
        <x-mane::link navigate :href="route('login')" :text="__('log in')" />
    </x-slot:footer>
</x-layouts::auth>
