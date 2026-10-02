<?php

use App\Domain\Platform\Health\HealthCheck;
use App\Domain\Platform\Health\PlatformHealth;
use App\Domain\Platform\Models\PlatformTenant;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::platform')] #[Title('Platform')] class extends Component {
    /**
     * @return list<HealthCheck>
     */
    #[Computed]
    public function checks(): array
    {
        return app(PlatformHealth::class)->checks();
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function tenantCounts(): array
    {
        $counts = PlatformTenant::query()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            PlatformTenant::STATUS_ACTIVE => (int) ($counts[PlatformTenant::STATUS_ACTIVE] ?? 0),
            PlatformTenant::STATUS_SUSPENDED => (int) ($counts[PlatformTenant::STATUS_SUSPENDED] ?? 0),
            PlatformTenant::STATUS_ARCHIVED => (int) ($counts[PlatformTenant::STATUS_ARCHIVED] ?? 0),
        ];
    }
}; ?>

<section class="flex w-full flex-col gap-6">
    <x-mane::page-header :title="__('Platform')" :description="__('Health of the system and of the spaces it hosts.')">
        <x-slot:actions>
            <x-mane::button variant="secondary" icon="arrow-path" :text="__('Refresh')" wire:click="$refresh" loading="$refresh" data-test="refresh-health" />
        </x-slot:actions>
    </x-mane::page-header>

    @if (session('status') === 'impersonation-ended')
        <x-mane::alert tone="success" :text="__('The support session has ended.')" data-test="impersonation-ended" />
    @elseif (session('status') === 'impersonation-expired')
        <x-mane::alert tone="info" :text="__('The support session has expired: you are back on your own account.')" data-test="impersonation-expired" />
    @endif

    <div class="grid gap-4 sm:grid-cols-3">
        <x-mane::stat :value="$this->tenantCounts['active']" :label="__('Active spaces')" />
        <x-mane::stat :value="$this->tenantCounts['suspended']" :label="__('Suspended spaces')" />
        <x-mane::stat :value="$this->tenantCounts['archived']" :label="__('Archived spaces')" />
    </div>

    <x-mane::card>
        <div class="flex flex-col gap-4">
            <x-mane::section-header :level="2" :title="__('Health')" :description="__('Checked when the page loads.')" />

            <ul class="divide-y divide-line" data-test="health-checks">
                @foreach ($this->checks as $check)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="health-{{ $check->key }}">
                        <span class="font-medium text-fg">{{ $check->label }}</span>

                        <span class="flex items-center gap-3">
                            <span class="text-sm text-fg-muted">{{ $check->detail }}</span>
                            <x-mane::status
                                :tone="match ($check->status) { 'critical' => 'danger', 'warning' => 'warning', default => 'success' }"
                                :text="match ($check->status) { 'critical' => __('Critical'), 'warning' => __('Warning'), default => __('OK') }"
                            />
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </x-mane::card>
</section>
