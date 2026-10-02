@php
    /** @var App\Domain\Platform\Models\Impersonation $impersonation */
@endphp

<div class="border-b border-line bg-surface-sunken px-4 py-3" role="region" aria-label="{{ __('Support session') }}" data-test="impersonation-banner">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3">
        <x-mane::alert
            tone="warning"
            :title="__('Support session: you are signed in as :name', ['name' => $userName])"
            :text="__('Read only, until :time. Reason: :reason', ['time' => $impersonation->expires_at->format('H:i'), 'reason' => $impersonation->reason])"
        />

        <form method="POST" action="{{ route('impersonation.stop') }}">
            @csrf
            <x-mane::button type="submit" variant="secondary" icon="arrow-uturn-left" :text="__('End the support session')" data-test="stop-impersonation-button" />
        </form>
    </div>
</div>
