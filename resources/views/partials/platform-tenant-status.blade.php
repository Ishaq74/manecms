<x-mane::status
    :tone="match ($status) { 'suspended' => 'danger', 'archived' => 'muted', default => 'success' }"
    :text="match ($status) { 'suspended' => __('Suspended'), 'archived' => __('Archived'), default => __('Active') }"
/>
