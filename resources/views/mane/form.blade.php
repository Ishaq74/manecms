{{--
    Livewire form contract (§281), paired with App\Livewire\Concerns\HasFormContract:
    error summary announced on submit, unsaved-changes notice, busy state while the
    request runs, and a submit guard that honours a disabled (aria-disabled) submit.
--}}
@props([
    'dirtyNotice' => true,
])

<form
    {{ $attributes->class('flex flex-col gap-stack') }}
    wire:loading.attr="aria-busy"
    x-data
    x-on:submit.capture="if ($el.querySelector('[data-mane-submit][aria-disabled=true]')) { $event.preventDefault(); $event.stopImmediatePropagation(); }"
>
    @if ($errors->any())
        <x-mane::alert tone="danger" :title="trans_choice('{1} The form contains one error.|[2,*] The form contains :count errors.', $errors->count())" data-test="form-error-summary" />
    @endif

    {{ $slot }}

    @if ($dirtyNotice)
        <p wire:dirty class="text-sm text-fg-muted" data-test="form-dirty-notice">{{ __('You have unsaved changes.') }}</p>
    @endif

    @isset($actions)
        <div class="flex flex-wrap items-center justify-end gap-3">{{ $actions }}</div>
    @endisset
</form>
