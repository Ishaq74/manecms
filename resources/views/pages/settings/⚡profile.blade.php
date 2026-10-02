<?php

use App\Concerns\ProfileValidationRules;
use App\Livewire\Concerns\HasFormContract;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::sidebar')] #[Title('Profile settings')] class extends Component {
    use HasFormContract;
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->formSucceeded(__('Profile updated.'));
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
    <x-mane::form wire:submit="updateProfileInformation">
        <x-mane::input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

        <x-mane::input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

        @if ($this->hasUnverifiedEmail)
            <x-mane::alert
                tone="warning"
                :title="__('Your email address is unverified.')"
                :text="session('status') === 'verification-link-sent' ? __('A new verification link has been sent to your email address.') : null"
            />

            <div>
                <x-mane::button
                    variant="secondary"
                    size="sm"
                    wire:click="resendVerificationNotification"
                    loading="resendVerificationNotification"
                    :text="__('Click here to re-send the verification email.')"
                />
            </div>
        @endif

        <x-slot:actions>
            <x-mane::button variant="ghost" wire:click="resetForm" :text="__('Discard changes')" data-test="reset-profile-form" />
            <x-mane::button type="submit" loading="updateProfileInformation" data-test="update-profile-button" :text="__('Save')" />
        </x-slot:actions>
    </x-mane::form>

    @if ($this->showDeleteUser)
        <x-mane::divider />

        <livewire:pages::settings.delete-user-form />
    @endif
</x-pages::settings.layout>
