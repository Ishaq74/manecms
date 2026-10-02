<?php

use App\Domain\Authorization\Actions\CreateRole;
use App\Domain\Authorization\Actions\DeleteRole;
use App\Domain\Authorization\Actions\UpdateRole;
use App\Domain\Authorization\Models\Role;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\PermissionRegistry;
use App\Domain\Platform\Errors\DomainError;
use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Permissions\TenancyPermission;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new #[Layout('layouts::sidebar')] #[Title('Roles')] class extends Component {
    use Interactions;

    public ?string $editingRoleId = null;

    public string $name = '';

    /** @var list<string> */
    public array $permissions = [];

    public bool $showRoleModal = false;

    public function mount(): void
    {
        $this->authorize(TenancyPermission::RoleManage->key());
    }

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles(): Collection
    {
        return app(TenantContext::class)->tenant()->roles()
            ->withCount('members')
            ->ordered()
            ->get();
    }

    /**
     * Permissions a custom role may hold, grouped by context.
     *
     * @return array<string, array<string, string>>
     */
    #[Computed]
    public function grantablePermissions(): array
    {
        $groups = [];

        foreach (app(PermissionRegistry::class)->all() as $key => $permission) {
            if ($permission->capability()->isGrantableToCustomRoles()) {
                $groups[$this->contextLabel($key)][$key] = $permission->label();
            }
        }

        return $groups;
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function permissionLabels(): array
    {
        return array_map(fn (Permission $permission): string => $permission->label(), app(PermissionRegistry::class)->all());
    }

    public function create(): void
    {
        $this->authorize(TenancyPermission::RoleManage->key());

        $this->reset('editingRoleId', 'name', 'permissions');
        $this->resetValidation();
        $this->showRoleModal = true;
    }

    public function edit(string $roleId): void
    {
        $this->authorize(TenancyPermission::RoleManage->key());

        $role = app(TenantContext::class)->tenant()->roles()->findOrFail($roleId);
        abort_if($role->isSystem(), 404);

        $this->editingRoleId = $role->id;
        $this->name = $role->name;
        $this->permissions = $role->permissionKeys();
        $this->resetValidation();
        $this->showRoleModal = true;
    }

    public function save(CreateRole $createRole, UpdateRole $updateRole): void
    {
        $this->attempt(function () use ($createRole, $updateRole): void {
            $permissions = array_values($this->permissions);

            $this->editingRoleId === null
                ? $createRole($this->user(), $this->name, $permissions)
                : $updateRole($this->user(), $this->editingRoleId, $this->name, $permissions);

            $this->showRoleModal = false;
            $this->toast()->success($this->editingRoleId === null ? __('Role created.') : __('Role updated.'))->send();
        });
    }

    public function delete(DeleteRole $deleteRole, string $roleId): void
    {
        $this->attempt(function () use ($deleteRole, $roleId): void {
            $deleteRole($this->user(), $roleId);

            $this->toast()->success(__('Role deleted.'))->send();
        });
    }

    private function contextLabel(string $key): string
    {
        return match (strstr($key, '.', true)) {
            'tenancy' => __('Space and members'),
            'audit' => __('Audit'),
            default => (string) strstr($key, '.', true),
        };
    }

    private function attempt(Closure $action): void
    {
        try {
            $action();
        } catch (DomainError $error) {
            $this->toast()->error($error->getMessage())->send();
        } finally {
            unset($this->roles);
        }
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}; ?>

<section class="flex w-full flex-col gap-6">
    <x-mane::page-header :title="__('Roles')" :description="__('A role is a set of permissions. System roles cannot be changed; create your own for finer access.')">
        <x-slot:actions>
            <x-mane::button icon="plus" :text="__('Create a role')" wire:click="create" loading="create" data-test="create-role-button" />
        </x-slot:actions>
    </x-mane::page-header>

    @error('role')
        <x-mane::alert tone="danger" :text="$message" data-test="role-error" />
    @enderror

    <div class="grid gap-4 md:grid-cols-2" data-test="roles">
        @foreach ($this->roles as $role)
            @php($keys = $role->permissionKeys())
            <x-mane::card wire:key="role-{{ $role->id }}">
                <div class="flex h-full flex-col gap-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex flex-col gap-1">
                            <x-mane::section-header :level="2" :title="$role->label()" />
                            <p class="text-sm text-fg-muted">
                                {{ trans_choice(':count member|:count members', $role->members_count, ['count' => $role->members_count]) }}
                                · {{ trans_choice(':count permission|:count permissions', count($keys), ['count' => count($keys)]) }}
                            </p>
                        </div>

                        @if ($role->isSystem())
                            <x-mane::badge variant="muted" size="sm" icon="lock-closed" :text="__('System')" />
                        @else
                            <div class="flex items-center gap-1">
                                <x-mane::icon-button icon="pencil-square" size="sm" :label="__('Edit :role', ['role' => $role->name])" wire:click="edit('{{ $role->id }}')" data-test="edit-role-{{ $role->key }}" />
                                <x-mane::icon-button
                                    icon="trash"
                                    size="sm"
                                    :label="__('Delete :role', ['role' => $role->name])"
                                    wire:click="delete('{{ $role->id }}')"
                                    wire:confirm="{{ __('Delete the role :role?', ['role' => $role->name]) }}"
                                    data-test="delete-role-{{ $role->key }}"
                                />
                            </div>
                        @endif
                    </div>

                    <ul class="flex flex-wrap gap-2">
                        @forelse ($keys as $key)
                            <li wire:key="role-{{ $role->id }}-{{ $key }}">
                                <x-mane::badge variant="secondary" size="sm" :text="$this->permissionLabels[$key] ?? $key" />
                            </li>
                        @empty
                            <li class="text-sm text-fg-muted">{{ __('No permission') }}</li>
                        @endforelse
                    </ul>
                </div>
            </x-mane::card>
        @endforeach
    </div>

    <x-mane::modal id="role-form" size="lg" wire="showRoleModal" :title="$editingRoleId === null ? __('Create a role') : __('Edit the role')">
        <x-mane::form wire:submit="save" :dirty-notice="false">
            <x-mane::input wire:model="name" :label="__('Name')" required maxlength="80" data-test="role-name" />

            @foreach ($this->grantablePermissions as $group => $options)
                <fieldset class="flex flex-col gap-2" wire:key="permission-group-{{ $loop->index }}">
                    <legend class="mb-1 text-sm font-medium text-fg">{{ $group }}</legend>

                    @foreach ($options as $key => $label)
                        <x-mane::checkbox wire:model="permissions" value="{{ $key }}" :label="$label" wire:key="permission-{{ $key }}" />
                    @endforeach
                </fieldset>
            @endforeach

            <p class="text-sm text-fg-muted">{{ __('Privileged permissions (security, ownership, roles) stay with the owner and admins.') }}</p>

            @error('permissions')
                <p class="text-sm font-medium text-danger">{{ $message }}</p>
            @enderror

            <x-slot:actions>
                <x-mane::button variant="ghost" :text="__('Cancel')" wire:click="$set('showRoleModal', false)" />
                <x-mane::button type="submit" loading="save" :text="__('Save')" data-test="save-role-form-button" />
            </x-slot:actions>
        </x-mane::form>
    </x-mane::modal>
</section>
