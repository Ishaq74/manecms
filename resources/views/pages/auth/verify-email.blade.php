<x-layouts::auth :title="__('Email verification')">
    <div class="mt-4 flex flex-col gap-6">
        <p class="text-center text-zinc-600 dark:text-zinc-400">
            {{ __('Please verify your email address by clicking on the link we just emailed to you.') }}
        </p>

        @if (session('status') == 'verification-link-sent')
            <p class="text-center font-medium text-green-600 dark:text-green-400">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </p>
        @endif

        <div class="flex flex-col items-center justify-between space-y-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-button submit block :text="__('Resend verification email')" />
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-button flat submit :text="__('Log out')" data-test="logout-button" class="cursor-pointer text-sm" />
            </form>
        </div>
    </div>
</x-layouts::auth>
