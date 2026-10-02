<?php

use App\Domain\Platform\Actions\SetTenantSuspension;
use App\Domain\Platform\ImpersonationSession;
use App\Domain\Platform\Models\PlatformTenant;
use App\Domain\Platform\Models\PlatformTenantMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new #[Layout('layouts::platform')] #[Title('Tenant')] class extends Component {
    use Interactions;

    #[Locked]
    public string $tenantId = '';

    public string $suspensionReason = '';

    public bool $showSuspendModal = false;

    public ?int $impersonatedUserId = null;

    public string $impersonationReason = '';

    public string $impersonationMinutes = '15';

    public bool $showImpersonateModal = false;

    public function mount(string $tenant): void
    {
        $this->tenantId = $tenant;

        abort_if($this->tenant === null, 404);
    }

    #[Computed]
    public function tenant(): ?PlatformTenant
    {
        return PlatformTenant::query()->find($this->tenantId);
    }

    /**
     * @return Collection<int, PlatformTenantMember>
     */
    #[Computed]
    public function members(): Collection
    {
        return PlatformTenantMember::query()
            ->where('tenant_id', $this->tenantId)
            ->orderByDesc('is_owner')
            ->orderBy('name')
            ->get();
    }

    public function suspend(SetTenantSuspension $setTenantSuspension): void
    {
        $setTenantSuspension->suspend($this->operator(), $this->tenantId, $this->suspensionReason);

        $this->reset('suspensionReason', 'showSuspendModal');
        unset($this->tenant);
        $this->toast()->success(__('Space suspended.'))->send();
    }

    public function reactivate(SetTenantSuspension $setTenantSuspension): void
    {
        $setTenantSuspension->reactivate($this->operator(), $this->tenantId);

        unset($this->tenant);
        $this->toast()->success(__('Space reactivated.'))->send();
    }

    public function prepareImpersonation(int $userId): void
    {
        abort_unless($this->members->contains(fn (PlatformTenantMember $member): bool => $member->user_id === $userId && $member->canBeImpersonated()), 404);

        $this->impersonatedUserId = $userId;
        $this->reset('impersonationReason');
        $this->resetValidation();
        $this->showImpersonateModal = true;
    }

    public function impersonate(ImpersonationSession $impersonation): void
    {
        abort_unless($this->members->contains(fn (PlatformTenantMember $member): bool => $member->user_id === $this->impersonatedUserId), 404);

        $impersonation->start($this->operator(), (int) $this->impersonatedUserId, $this->impersonationReason, (int) $this->impersonationMinutes);

        $this->redirectRoute('dashboard');
    }

    #[Computed]
    public function impersonatedName(): string
    {
        return $this->members->firstWhere('user_id', $this->impersonatedUserId)->name ?? '';
    }

    private function operator(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}; ?>

<section class="flex w-full flex-col gap-6">
    <x-mane::breadcrumb :items="[['label' => __('Tenants'), 'href' => route('platform.tenants.index')], ['label' => $this->tenant->name]]" />

    <x-mane::page-header :title="$this->tenant->name" :description="__('Created on :date', ['date' => $this->tenant->created_at?->translatedFormat('j F Y')])">
        <x-slot:actions>
            @include('partials.platform-tenant-status', ['status' => $this->tenant->status])
        </x-slot:actions>
    </x-mane::page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-mane::stat :value="$this->tenant->members_count" :label="__('Members')" />
        <x-mane::stat :value="$this->tenant->workspaces_count" :label="__('Active workspaces')" />
        <x-mane::stat :value="$this->tenant->require_mfa ? __('Required') : __('Optional')" :label="__('Two-factor authentication')" />
    </div>

    <x-mane::card>
        <div class="flex flex-col items-start gap-4">
            <x-mane::section-header :level="2" :title="__('Suspension')" :description="__('A suspended space stays intact, but its members see the reason instead of their workspaces and its jobs stop.')" />

            @if ($this->tenant->isSuspended())
                <x-mane::alert tone="danger" :title="__('Suspended on :date', ['date' => $this->tenant->suspended_at?->translatedFormat('j F Y H:i')])" :text="$this->tenant->suspension_reason" data-test="suspension-reason" />

                <x-mane::button variant="secondary" icon="play" :text="__('Reactivate the space')" wire:click="reactivate" wire:confirm="{{ __('Reactivate this space?') }}" loading="reactivate" data-test="reactivate-tenant-button" />
            @elseif ($this->tenant->archived_at === null)
                <x-mane::button variant="danger" icon="pause" :text="__('Suspend the space')" wire:click="$set('showSuspendModal', true)" data-test="suspend-tenant-button" />
            @else
                <p class="text-sm text-fg-muted">{{ __('Archived by its owner: nothing to suspend.') }}</p>
            @endif
        </div>
    </x-mane::card>

    <x-mane::card>
        <div class="flex flex-col gap-4">
            <x-mane::section-header :level="2" :title="__('Members')" :description="__('A support session signs you in as the member, read only, for 30 minutes at most. It is audited.')" />

            <ul class="divide-y divide-line" data-test="platform-members">
                @foreach ($this->members as $member)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="platform-member-{{ $member->id }}">
                        <div class="flex min-w-0 flex-col">
                            <span class="truncate font-medium text-fg">{{ $member->name }}</span>
                            <span class="text-sm text-fg-muted">{{ $member->email }} · {{ $member->roleLabel() }}</span>
                        </div>

                        @if ($member->canBeImpersonated())
                            <x-mane::button size="sm" variant="ghost" icon="user-circle" :text="__('Support session')" wire:click="prepareImpersonation({{ $member->user_id }})" data-test="impersonate-{{ $member->user_id }}" />
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </x-mane::card>

    <x-mane::modal id="suspend-tenant" size="lg" wire="showSuspendModal" :title="__('Suspend this space?')">
        <x-mane::form wire:submit="suspend" :dirty-notice="false">
            <x-mane::textarea wire:model="suspensionReason" :label="__('Reason shown to the members')" required minlength="10" maxlength="500" data-test="suspension-reason-input" />

            <x-slot:actions>
                <x-mane::button variant="ghost" :text="__('Cancel')" wire:click="$set('showSuspendModal', false)" />
                <x-mane::button type="submit" variant="danger" loading="suspend" :text="__('Suspend the space')" data-test="confirm-suspend-button" />
            </x-slot:actions>
        </x-mane::form>
    </x-mane::modal>

    <x-mane::modal id="impersonate-member" size="lg" wire="showImpersonateModal" :title="__('Support session as :name', ['name' => $this->impersonatedName])">
        <x-mane::form wire:submit="impersonate" :dirty-notice="false">
            <x-mane::textarea wire:model="impersonationReason" :label="__('Reason (ticket, request)')" required minlength="10" maxlength="500" data-test="impersonation-reason" />

            <x-mane::select wire:model="impersonationMinutes" :label="__('Duration')" :options="['5' => __(':count minutes', ['count' => 5]), '15' => __(':count minutes', ['count' => 15]), '30' => __(':count minutes', ['count' => 30])]" data-test="impersonation-minutes" />

            @error('user')
                <x-mane::alert tone="danger" :text="$message" />
            @enderror

            <x-slot:actions>
                <x-mane::button variant="ghost" :text="__('Cancel')" wire:click="$set('showImpersonateModal', false)" />
                <x-mane::button type="submit" loading="impersonate" :text="__('Start the support session')" data-test="start-impersonation-button" />
            </x-slot:actions>
        </x-mane::form>
    </x-mane::modal>
</section>
