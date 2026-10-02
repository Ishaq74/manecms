<x-mane::link :href="route('platform.tenants.show', $tenant->id)" navigate :text="$tenant->name" data-test="platform-tenant-{{ $tenant->id }}" />
