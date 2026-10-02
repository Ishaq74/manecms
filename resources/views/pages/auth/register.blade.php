<x-layouts::auth
    :title="__('Register')"
    :heading="__('Create an account')"
    :description="__('Enter your details below to create your account')"
    :status="session('status')"
>
    <x-mane::form method="POST" action="{{ route('register.store') }}" :dirty-notice="false">
        @csrf

        <x-mane::input
            name="name"
            :label="__('Name')"
            :value="old('name')"
            type="text"
            required
            autofocus
            autocomplete="name"
            :placeholder="__('Full name')"
        />

        <x-mane::input
            name="email"
            :label="__('Email address')"
            :value="old('email')"
            type="email"
            required
            autocomplete="email"
            placeholder="email@example.com"
        />

        <x-mane::password name="password" :label="__('Password')" required autocomplete="new-password" rules />

        <x-mane::password name="password_confirmation" :label="__('Confirm password')" required autocomplete="new-password" />

        <x-mane::button type="submit" block data-test="register-user-button" :text="__('Create account')" />
    </x-mane::form>

    <x-slot:footer>
        {{ __('Already have an account?') }}
        <x-mane::link navigate :href="route('login')" :text="__('Log in')" />
    </x-slot:footer>
</x-layouts::auth>
