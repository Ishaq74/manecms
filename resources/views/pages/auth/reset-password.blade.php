<x-layouts::auth
    :title="__('Reset password')"
    :heading="__('Reset password')"
    :description="__('Please enter your new password below')"
    :status="session('status')"
>
    <x-mane::form method="POST" action="{{ route('password.update') }}" :dirty-notice="false">
        @csrf
        <input type="hidden" name="token" value="{{ request()->route('token') }}">

        <x-mane::input
            name="email"
            :value="old('email', request('email'))"
            :label="__('Email')"
            type="email"
            required
            autocomplete="email"
        />

        <x-mane::password name="password" :label="__('Password')" required autocomplete="new-password" rules />

        <x-mane::password name="password_confirmation" :label="__('Confirm password')" required autocomplete="new-password" />

        <x-mane::button type="submit" block data-test="reset-password-button" :text="__('Reset password')" />
    </x-mane::form>
</x-layouts::auth>
