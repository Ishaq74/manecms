{{-- A horizontal rule, optionally labelled ("or continue with"). Draws no background, so it sits on any surface. --}}
@props([
    'label' => null,
])

@if ($label)
    <div {{ $attributes->merge(['role' => 'separator', 'aria-label' => $label])->class('flex items-center gap-3 text-xs font-medium uppercase tracking-wide text-fg-muted') }}>
        <span class="h-px flex-1 bg-line" aria-hidden="true"></span>
        <span aria-hidden="true">{{ $label }}</span>
        <span class="h-px flex-1 bg-line" aria-hidden="true"></span>
    </div>
@else
    <hr {{ $attributes->class('border-line') }} />
@endif
