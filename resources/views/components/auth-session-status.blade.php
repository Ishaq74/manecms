@props([
    'status',
])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-secondary-600 dark:text-secondary-400']) }}>
        {{ $status }}
    </div>
@endif
