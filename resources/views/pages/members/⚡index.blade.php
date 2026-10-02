<?php

use App\Domain\Authorization\PolicyEngine;
use App\Domain\Platform\Errors\DomainError;
use App\Domain\Tenancy\Actions\InviteMember;
use App\Domain\Tenancy\Actions\LeaveTenant;
use App\Domain\Tenancy\Actions\RemoveMember;
use App\Domain\Tenancy\Actions\RestrictMemberWorkspaces;
use App\Domain\Tenancy\Actions\RevokeInvitation;
use App\Domain\Tenancy\Actions\UpdateMemberRole;
use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Models\TenantInvitation;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Permissions\TenancyPermission;
use App\Livewire\DataTable\Column;
use App\Livewire\DataTable\Filter;
use App\Livewire\DataTable\WithDataTable;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new #[Layout('layouts::sidebar')] #[Title('Members')] class extends Component {
    use Interactions;
    use WithDataTable;

    private const int INVITATION_HISTORY = 20;

    public string $inviteEmail = '';

    public string $inviteRoleId = '';

    /** @var list<string> */
    public array $inviteWorkspaceIds = [];

    public bool $showInviteModal = false;

    public ?string $managedMemberId = null;

    public string $managedRoleId = '';

    /** @var list<string> */
    public array $managedWorkspaceIds = [];

    public bool $showManageModal = false;

    public bool $showLeaveModal = false;

    public function mount(): void
    {
        $this->authorize(TenancyPermission::MemberView->key());
    }

    /**
     * Row level security limits every query to the current tenant.
     *
     * @return Builder<TenantMember>
     */
    protected function tableQuery(): Builder
    {
        $this->authorize(TenancyPermission::MemberView->key());

        return $this->tenantMembers()
            ->select('tenant_members.*')
            ->join('users', 'users.id', '=', 'tenant_members.user_id')
            ->with(['user:id,name,email', 'role', 'restrictedWorkspaces:id,name']);
    }

    protected function tableColumns(): array
    {
        return [
            Column::make('users.name', __('Name'))
                ->sortable()
                ->searchable()
                ->alwaysVisible()
                ->format(fn (TenantMember $member): HtmlString => new HtmlString(view('partials.member-name', ['member' => $member, 'isCurrentUser' => $member->user_id === Auth::id()])->render())),
            Column::make('users.email', __('Email'))->sortable()->searchable()
                ->format(fn (TenantMember $member): string => $member->user->email),
            Column::make('role', __('Role'))
                ->format(fn (TenantMember $member): string => $member->role->label()),
            Column::make('workspaces', __('Workspaces'))
                ->format(fn (TenantMember $member): string => $member->is_owner || $member->restrictedWorkspaces->isEmpty()
                    ? __('All workspaces')
                    : $member->restrictedWorkspaces->pluck('name')->sort()->join(', ')),
            Column::make('tenant_members.created_at', __('Joined'))->sortable()
                ->format(fn (TenantMember $member): string => $member->created_at?->translatedFormat('j M Y') ?? ''),
            Column::make('actions', __('Actions'))
                ->alwaysVisible()
                ->format(fn (TenantMember $member): HtmlString => new HtmlString($this->canManage($member)
                    ? view('partials.member-actions', ['member' => $member])->render()
                    : '')),
        ];
    }

    protected function tableFilters(): array
    {
        return [
            Filter::make('role', __('Role'), $this->roleOptions(includeOwner: true))
                ->using(fn (Builder $query, string $roleId) => $query->where('tenant_members.role_id', $roleId)),
        ];
    }

    protected function tableKey(): string
    {
        return 'tenancy.members';
    }

    protected function tableDefaultSort(): array
    {
        return ['users.name', 'asc'];
    }

    protected function tableCaption(): string
    {
        return __('Members of this space');
    }

    /**
     * @return Collection<int, TenantInvitation>
     */
    #[Computed]
    public function invitations(): Collection
    {
        return TenantInvitation::query()
            ->with(['role', 'inviter:id,name'])
            ->latest()
            ->limit(self::INVITATION_HISTORY)
            ->get();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function workspaceOptions(): array
    {
        return app(TenantContext::class)->tenant()->activeWorkspaces()->pluck('name', 'id')->all();
    }

    /**
     * @return array<string, string>
     */
    public function roleOptions(bool $includeOwner = false): array
    {
        return app(TenantContext::class)->tenant()->roles()
            ->when(! $includeOwner, fn ($query) => $query->assignable())
            ->ordered()
            ->get()
            ->mapWithKeys(fn ($role): array => [$role->id => $role->label()])
            ->all();
    }

    public function openInvite(): void
    {
        $this->authorize(TenancyPermission::MemberInvite->key());

        $this->reset('inviteEmail', 'inviteWorkspaceIds');
        $this->inviteRoleId = (string) array_key_last($this->roleOptions());
        $this->resetValidation();
        $this->showInviteModal = true;
    }

    public function invite(InviteMember $inviteMember): void
    {
        $this->attempt(function () use ($inviteMember): void {
            $invitation = $inviteMember($this->user(), $this->inviteEmail, $this->inviteRoleId, array_values($this->inviteWorkspaceIds));

            $this->showInviteModal = false;
            unset($this->invitations);
            $this->toast()->success(__('Invitation sent to :email.', ['email' => $invitation->email]))->send();
        });
    }

    public function revokeInvitation(RevokeInvitation $revokeInvitation, string $invitationId): void
    {
        $this->attempt(function () use ($revokeInvitation, $invitationId): void {
            $revokeInvitation($this->user(), $invitationId);

            unset($this->invitations);
            $this->toast()->success(__('Invitation revoked.'))->send();
        });
    }

    public function manage(string $memberId): void
    {
        $member = $this->tenantMembers()->with(['role', 'restrictedWorkspaces:id'])->findOrFail($memberId);
        abort_unless($this->canManage($member), 403);

        $this->managedMemberId = $member->id;
        $this->managedRoleId = $member->role_id;
        $this->managedWorkspaceIds = $member->restrictedWorkspaces->modelKeys();
        $this->resetValidation();
        $this->showManageModal = true;
    }

    /**
     * @return array{role: bool, restrict: bool, remove: bool}
     */
    #[Computed]
    public function managedAbilities(): array
    {
        $member = $this->managedMemberId === null ? null : $this->tenantMembers()->with('role')->find($this->managedMemberId);

        return $member === null ? ['role' => false, 'restrict' => false, 'remove' => false] : $this->abilitiesOn($member);
    }

    #[Computed]
    public function managedMember(): ?TenantMember
    {
        return $this->managedMemberId === null ? null : $this->tenantMembers()->with('user:id,name,email')->find($this->managedMemberId);
    }

    public function saveRole(UpdateMemberRole $updateMemberRole): void
    {
        $this->attempt(function () use ($updateMemberRole): void {
            $updateMemberRole($this->user(), (string) $this->managedMemberId, $this->managedRoleId);

            $this->toast()->success(__('Role updated.'))->send();
        });
    }

    public function saveRestriction(RestrictMemberWorkspaces $restrictMemberWorkspaces): void
    {
        $this->attempt(function () use ($restrictMemberWorkspaces): void {
            $restrictMemberWorkspaces($this->user(), (string) $this->managedMemberId, array_values($this->managedWorkspaceIds));

            $this->toast()->success(__('Workspace access updated.'))->send();
        });
    }

    public function removeMember(RemoveMember $removeMember): void
    {
        $this->attempt(function () use ($removeMember): void {
            $removeMember($this->user(), (string) $this->managedMemberId);

            $this->showManageModal = false;
            $this->managedMemberId = null;
            $this->toast()->success(__('Member removed.'))->send();
        });
    }

    public function leave(LeaveTenant $leaveTenant): void
    {
        $this->attempt(function () use ($leaveTenant): void {
            $leaveTenant($this->user());

            $this->redirectRoute('dashboard', navigate: true);
        });
    }

    #[Computed]
    public function canLeave(): bool
    {
        return ! app(TenantContext::class)->member()->is_owner;
    }

    private function canManage(TenantMember $member): bool
    {
        return in_array(true, $this->abilitiesOn($member), true);
    }

    /**
     * @return array{role: bool, restrict: bool, remove: bool}
     */
    private function abilitiesOn(TenantMember $member): array
    {
        $engine = app(PolicyEngine::class);
        $user = $this->user();

        return [
            'role' => $engine->decide($user, TenancyPermission::MemberUpdateRole, targetMember: $member)->allows(),
            'restrict' => $engine->decide($user, TenancyPermission::MemberRestrict, targetMember: $member)->allows(),
            'remove' => $engine->decide($user, TenancyPermission::MemberRemove, targetMember: $member)->allows(),
        ];
    }

    /**
     * Business refusals (denied, invalid state) become a toast; validation errors stay on their fields.
     */
    private function attempt(Closure $action): void
    {
        try {
            $action();
        } catch (DomainError $error) {
            $this->toast()->error($error->getMessage())->send();
        } finally {
            unset($this->tableRows, $this->managedAbilities, $this->managedMember);
        }
    }

    /**
     * Row level security also shows the user their own memberships of other tenants.
     *
     * @return Builder<TenantMember>
     */
    private function tenantMembers(): Builder
    {
        return TenantMember::query()->where('tenant_members.tenant_id', app(TenantContext::class)->tenant()->id);
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}; ?>

<section class="flex w-full flex-col gap-6">
    <x-mane::page-header :title="__('Members')" :description="__('Who belongs to this space, with which role and on which workspaces.')">
        <x-slot:actions>
            @if ($this->canLeave)
                <x-mane::button variant="ghost" icon="arrow-right-start-on-rectangle" :text="__('Leave the space')" wire:click="$set('showLeaveModal', true)" data-test="leave-tenant-button" />
            @endif

            @can('tenancy.member.invite')
                <x-mane::button icon="user-plus" :text="__('Invite')" wire:click="openInvite" loading="openInvite" data-test="invite-member-button" />
            @endcan
        </x-slot:actions>
    </x-mane::page-header>

    @include('mane.livewire.data-table')

    <x-mane::card>
        <div class="flex flex-col gap-4">
            <x-mane::section-header :level="2" :title="__('Invitations')" :description="__('An invitation is valid for 7 days. Only its recipient can accept it.')" />

            @if ($this->invitations->isEmpty())
                <x-mane::empty-state kind="first-use" :title="__('No invitation yet')" :description="__('Invite people by email: they join with the role you choose.')" />
            @else
                <ul class="divide-y divide-line" data-test="invitations">
                    @foreach ($this->invitations as $invitation)
                        @php($status = $invitation->status())
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="invitation-{{ $invitation->id }}">
                            <div class="flex min-w-0 flex-col">
                                <span class="truncate font-medium text-fg">{{ $invitation->email }}</span>
                                <span class="text-sm text-fg-muted">
                                    {{ $invitation->role->label() }}
                                    · {{ __('Invited by :name', ['name' => $invitation->inviter->name ?? __('a former member')]) }}
                                    · {{ $status === \App\Domain\Tenancy\Enums\InvitationStatus::Pending ? __('expires on :date', ['date' => $invitation->expires_at->translatedFormat('j M Y')]) : $invitation->updated_at?->translatedFormat('j M Y') }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <x-mane::status
                                    :tone="match ($status) { \App\Domain\Tenancy\Enums\InvitationStatus::Pending => 'info', \App\Domain\Tenancy\Enums\InvitationStatus::Accepted => 'success', \App\Domain\Tenancy\Enums\InvitationStatus::Revoked => 'muted', \App\Domain\Tenancy\Enums\InvitationStatus::Expired => 'warning' }"
                                    :text="$status->label()"
                                />

                                @if ($status === \App\Domain\Tenancy\Enums\InvitationStatus::Pending)
                                    @can('tenancy.member.invite')
                                        <x-mane::button
                                            size="sm"
                                            variant="ghost"
                                            :text="__('Revoke')"
                                            wire:click="revokeInvitation('{{ $invitation->id }}')"
                                            wire:confirm="{{ __('Revoke the invitation sent to :email?', ['email' => $invitation->email]) }}"
                                            data-test="revoke-invitation-{{ $invitation->id }}"
                                        />
                                    @endcan
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </x-mane::card>

    <x-mane::modal id="invite-member" size="lg" wire="showInviteModal" :title="__('Invite a member')">
        <x-mane::form wire:submit="invite" :dirty-notice="false">
            <x-mane::input wire:model="inviteEmail" type="email" :label="__('Email')" required maxlength="254" autocomplete="off" data-test="invite-email" />

            <x-mane::select wire:model="inviteRoleId" :label="__('Role')" :options="$this->roleOptions()" data-test="invite-role" />

            <fieldset class="flex flex-col gap-2">
                <legend class="mb-1 text-sm font-medium text-fg">{{ __('Workspaces') }}</legend>
                <p class="text-sm text-fg-muted">{{ __('Leave everything unchecked to give access to every workspace.') }}</p>

                @foreach ($this->workspaceOptions as $workspaceId => $workspaceName)
                    <x-mane::checkbox wire:model="inviteWorkspaceIds" value="{{ $workspaceId }}" :label="$workspaceName" wire:key="invite-workspace-{{ $workspaceId }}" />
                @endforeach

                @error('workspaces')
                    <p class="text-sm font-medium text-danger">{{ $message }}</p>
                @enderror
            </fieldset>

            <x-slot:actions>
                <x-mane::button variant="ghost" :text="__('Cancel')" wire:click="$set('showInviteModal', false)" />
                <x-mane::button type="submit" icon="paper-airplane" loading="invite" :text="__('Send the invitation')" data-test="send-invitation-button" />
            </x-slot:actions>
        </x-mane::form>
    </x-mane::modal>

    <x-mane::modal id="manage-member" size="lg" wire="showManageModal" :title="$this->managedMember?->user->name ?? __('Member')">
        <div class="flex flex-col gap-6">
            @if ($this->managedAbilities['role'])
                <x-mane::form wire:submit="saveRole" :dirty-notice="false">
                    <x-mane::select wire:model="managedRoleId" :label="__('Role')" :options="$this->roleOptions()" data-test="manage-role" />

                    <x-slot:actions>
                        <x-mane::button type="submit" loading="saveRole" :text="__('Change the role')" data-test="save-role-button" />
                    </x-slot:actions>
                </x-mane::form>
            @endif

            @if ($this->managedAbilities['restrict'])
                <x-mane::form wire:submit="saveRestriction" :dirty-notice="false">
                    <fieldset class="flex flex-col gap-2">
                        <legend class="mb-1 text-sm font-medium text-fg">{{ __('Workspaces') }}</legend>
                        <p class="text-sm text-fg-muted">{{ __('Leave everything unchecked to give access to every workspace.') }}</p>

                        @foreach ($this->workspaceOptions as $workspaceId => $workspaceName)
                            <x-mane::checkbox wire:model="managedWorkspaceIds" value="{{ $workspaceId }}" :label="$workspaceName" wire:key="manage-workspace-{{ $workspaceId }}" />
                        @endforeach

                        @error('workspaces')
                            <p class="text-sm font-medium text-danger">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <x-slot:actions>
                        <x-mane::button type="submit" variant="secondary" loading="saveRestriction" :text="__('Save the access')" data-test="save-restriction-button" />
                    </x-slot:actions>
                </x-mane::form>
            @endif

            @if ($this->managedAbilities['remove'])
                <div class="flex flex-col items-start gap-3 border-t border-line pt-4">
                    <x-mane::section-header :level="3" :title="__('Remove from the space')" :description="__('They lose access to every workspace at once. Their past actions stay in the audit trail.')" />

                    <x-mane::button
                        variant="danger"
                        icon="user-minus"
                        :text="__('Remove the member')"
                        wire:click="removeMember"
                        wire:confirm="{{ __('Remove this member from the space?') }}"
                        loading="removeMember"
                        data-test="remove-member-button"
                    />
                </div>
            @endif
        </div>
    </x-mane::modal>

    <x-mane::modal id="leave-tenant" size="md" wire="showLeaveModal" :title="__('Leave this space?')">
        <div class="flex flex-col gap-6">
            <p class="text-sm text-fg-muted">{{ __('You will lose access to its workspaces. An admin can invite you again.') }}</p>

            @error('leave')
                <x-mane::alert tone="danger" :text="$message" />
            @enderror

            <div class="flex justify-end gap-2">
                <x-mane::button variant="ghost" :text="__('Cancel')" wire:click="$set('showLeaveModal', false)" />
                <x-mane::button variant="danger" :text="__('Leave the space')" wire:click="leave" loading="leave" data-test="confirm-leave-button" />
            </div>
        </div>
    </x-mane::modal>
</section>
