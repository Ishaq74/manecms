<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <x-input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <div class="relative">
                <x-password
                    name="password"
                    :label="__('Password')"
                    required
                    autocomplete="current-password"
                    placeholder="{{ __('Password') }}"
                />

                @if (Route::has('password.request'))
                    <a
                        class="absolute top-0 text-sm end-0 text-zinc-600 underline dark:text-zinc-400"
                        href="{{ route('password.request') }}"
                        wire:navigate
                    >
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>

            <!-- Remember Me -->
            <x-checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <x-button submit block data-test="login-button" :text="__('Log in')" />
            </div>
        </form>

        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Don\'t have an account?') }}</span>
            <a class="underline" href="{{ route('register') }}" wire:navigate>{{ __('Sign up') }}</a>
        </div>
    </div>
</x-layouts::auth>
