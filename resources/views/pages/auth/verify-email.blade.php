<x-layouts::auth
    :title="__('Email verification')"
    :heading="__('Verify your email address')"
    :description="__('Please verify your email address by clicking on the link we just emailed to you.')"
    :status="session('status') === 'verification-link-sent' ? __('A new verification link has been sent to the email address you provided during registration.') : null"
>
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf

        <x-mane::button type="submit" block :text="__('Resend verification email')" />
    </form>

    <form method="POST" action="{{ route('logout') }}" class="flex justify-center">
        @csrf

        <x-mane::button variant="ghost" size="sm" type="submit" :text="__('Log out')" data-test="logout-button" />
    </form>
</x-layouts::auth>
