<?php

use App\Domain\Identity\Actions\ConfirmIdentity;
use App\Domain\Platform\Errors\DomainError;
use App\Domain\Tenancy\Actions\ArchiveTenant;
use App\Domain\Tenancy\Actions\RenameTenant;
use App\Domain\Tenancy\Actions\TransferOwnership;
use App\Domain\Tenancy\Actions\UpdateTenantSecurity;
use App\Domain\Tenancy\Context\TenantContext;
use App\Livewire\Concerns\HasFormContract;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::sidebar')] #[Title('Space settings')] class extends Component {
    use HasFormContract;

    public string $name = '';

    public bool $requireMfa = false;

    public string $transferMemberId = '';

    public string $transferPassword = '';

    public string $transferCode = '';

    public string $archiveConfirmation = '';

    public string $archivePassword = '';

    public string $archiveCode = '';

    public bool $showTransferModal = false;

    public bool $showArchiveModal = false;

    public function mount(TenantContext $context): void
    {
        $this->authorize('update', $context->tenant());

        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $tenant = app(TenantContext::class)->tenant();

        $this->name = $tenant->name;
        $this->requireMfa = $tenant->require_mfa;
    }

    /**
     * Members who could become the owner.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function transferCandidates(): array
    {
        return app(TenantContext::class)->tenant()->members()
            ->where('is_owner', false)
            ->join('users', 'users.id', '=', 'tenant_members.user_id')
            ->orderBy('users.name')
            ->pluck('users.name', 'tenant_members.id')
            ->all();
    }

    #[Computed]
    public function needsTwoFactorCode(): bool
    {
        return app(ConfirmIdentity::class)->needsCode($this->user());
    }

    /**
     * Rename the current tenant.
     */
    public function renameTenant(RenameTenant $renameTenant): void
    {
        $renameTenant($this->user(), $this->name);

        $this->formSucceeded(__('Space renamed.'));
    }

    public function updatedRequireMfa(UpdateTenantSecurity $updateTenantSecurity): void
    {
        try {
            $updateTenantSecurity($this->user(), $this->requireMfa);

            $this->formSucceeded($this->requireMfa ? __('Two-factor authentication is now required.') : __('Two-factor authentication is no longer required.'));
        } catch (DomainError $error) {
            $this->requireMfa = ! $this->requireMfa;
            $this->toast()->error($error->getMessage())->send();
        } catch (ValidationException $exception) {
            $this->requireMfa = ! $this->requireMfa;

            throw $exception;
        }
    }

    public function transferOwnership(TransferOwnership $transferOwnership): void
    {
        try {
            $transferOwnership($this->user(), $this->transferMemberId, $this->transferPassword, $this->transferCode === '' ? null : $this->transferCode);
        } finally {
            $this->reset('transferPassword', 'transferCode');
        }

        $this->showTransferModal = false;
        $this->redirectRoute('tenant.settings', app(TenantContext::class)->workspace(), navigate: true);
        $this->toast()->success(__('Ownership transferred. You are now an admin of this space.'))->flash()->send();
    }

    public function archiveTenant(ArchiveTenant $archiveTenant): void
    {
        try {
            $archiveTenant($this->user(), $this->archiveConfirmation, $this->archivePassword, $this->archiveCode === '' ? null : $this->archiveCode);
        } finally {
            $this->reset('archivePassword', 'archiveCode');
        }

        $this->redirectRoute('dashboard', navigate: true);
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}; ?>

<section class="mx-auto flex w-full max-w-2xl flex-col gap-6">
    <x-mane::page-header :title="__('Space settings')" :description="__('The space groups your workspaces, members and data.')" />

    <x-mane::card>
        <x-mane::form wire:submit="renameTenant">
            <x-mane::input wire:model="name" :label="__('Name')" type="text" required maxlength="120" data-test="tenant-name" />

            <x-slot:actions>
                <x-mane::button variant="ghost" wire:click="resetForm" :text="__('Discard changes')" data-test="reset-tenant-form" />
                <x-mane::button type="submit" loading="renameTenant" data-test="rename-tenant-button" :text="__('Save')" />
            </x-slot:actions>
        </x-mane::form>
    </x-mane::card>

    @can('tenancy.tenant.security')
        <x-mane::card>
            <div class="flex flex-col gap-4">
                <x-mane::section-header :level="2" :title="__('Security')" :description="__('Owners and admins without two-factor authentication are sent to their security settings until they enable it.')" />

                <x-mane::switch wire:model.live="requireMfa" :label="__('Require two-factor authentication')" data-test="require-mfa" />

                @error('requireMfa')
                    <x-mane::alert tone="warning" :text="$message" data-test="require-mfa-error" />
                @enderror
            </div>
        </x-mane::card>
    @endcan

    @can('tenancy.tenant.transfer')
        <x-mane::card>
            <div class="flex flex-col items-start gap-4">
                <x-mane::section-header :level="2" :title="__('Transfer ownership')" :description="__('The new owner gets every right on the space; you stay an admin.')" />

                @if ($this->transferCandidates === [])
                    <x-mane::empty-state kind="empty" :title="__('No other member yet')" :description="__('Invite someone before transferring the space.')" />
                @else
                    <x-mane::button variant="secondary" icon="arrows-right-left" :text="__('Transfer ownership')" wire:click="$set('showTransferModal', true)" data-test="transfer-ownership-button" />
                @endif
            </div>
        </x-mane::card>
    @endcan

    @can('tenancy.tenant.archive')
        <x-mane::card>
            <div class="flex flex-col items-start gap-4">
                <x-mane::section-header :level="2" :title="__('Archive the space')" :description="__('Nobody can open an archived space any more. Its data is kept.')" />

                <x-mane::button variant="danger" icon="archive-box" :text="__('Archive the space')" wire:click="$set('showArchiveModal', true)" data-test="archive-tenant-button" />
            </div>
        </x-mane::card>
    @endcan

    <x-mane::modal id="transfer-ownership" size="lg" wire="showTransferModal" :title="__('Transfer ownership')">
        <x-mane::form wire:submit="transferOwnership" :dirty-notice="false">
            <x-mane::select wire:model="transferMemberId" :label="__('New owner')" :options="$this->transferCandidates" :placeholder="__('Choose a member')" required data-test="transfer-member" />
            <x-mane::password wire:model="transferPassword" :label="__('Your password')" required autocomplete="current-password" data-test="transfer-password" />

            @if ($this->needsTwoFactorCode)
                <x-mane::input wire:model="transferCode" :label="__('Two-factor code')" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required data-test="transfer-code" />
            @endif

            <x-slot:actions>
                <x-mane::button variant="ghost" :text="__('Cancel')" wire:click="$set('showTransferModal', false)" />
                <x-mane::button type="submit" variant="danger" loading="transferOwnership" :text="__('Transfer ownership')" data-test="confirm-transfer-button" />
            </x-slot:actions>
        </x-mane::form>
    </x-mane::modal>

    <x-mane::modal id="archive-tenant" size="lg" wire="showArchiveModal" :title="__('Archive this space?')">
        <x-mane::form wire:submit="archiveTenant" :dirty-notice="false">
            <p class="text-sm text-fg-muted">{{ __('Type :name to confirm.', ['name' => app(App\Domain\Tenancy\Context\TenantContext::class)->tenant()->name]) }}</p>

            <x-mane::input wire:model="archiveConfirmation" :label="__('Name of the space')" required autocomplete="off" data-test="archive-confirmation" />
            <x-mane::password wire:model="archivePassword" :label="__('Your password')" required autocomplete="current-password" data-test="archive-password" />

            @if ($this->needsTwoFactorCode)
                <x-mane::input wire:model="archiveCode" :label="__('Two-factor code')" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required data-test="archive-code" />
            @endif

            <x-slot:actions>
                <x-mane::button variant="ghost" :text="__('Cancel')" wire:click="$set('showArchiveModal', false)" />
                <x-mane::button type="submit" variant="danger" loading="archiveTenant" :text="__('Archive the space')" data-test="confirm-archive-tenant-button" />
            </x-slot:actions>
        </x-mane::form>
    </x-mane::modal>
</section>
