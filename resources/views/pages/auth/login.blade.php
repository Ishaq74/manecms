<x-layouts::auth
    :title="__('Log in')"
    :heading="__('Log in to your account')"
    :description="__('Enter your email and password below to log in')"
    :status="session('status')"
>
    <x-passkey-verify />

    <x-mane::form method="POST" action="{{ route('login.store') }}" :dirty-notice="false">
        @csrf

        <x-mane::input
            name="email"
            :label="__('Email address')"
            :value="old('email')"
            type="email"
            required
            autofocus
            autocomplete="email"
            placeholder="email@example.com"
        />

        <div class="flex flex-col gap-2">
            <x-mane::password
                name="password"
                :label="__('Password')"
                required
                autocomplete="current-password"
            />

            @if (Route::has('password.request'))
                <x-mane::link class="self-end text-sm" navigate :href="route('password.request')" :text="__('Forgot your password?')" />
            @endif
        </div>

        <x-mane::checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

        <x-mane::button type="submit" block data-test="login-button" :text="__('Log in')" />
    </x-mane::form>

    <x-slot:footer>
        {{ __('Don\'t have an account?') }}
        <x-mane::link navigate :href="route('register')" :text="__('Sign up')" />
    </x-slot:footer>
</x-layouts::auth>
