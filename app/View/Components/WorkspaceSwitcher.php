<?php

namespace App\View\Components;

use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\Workspace;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\Component;

/**
 * Lists the active workspaces of every tenant the user belongs to.
 */
class WorkspaceSwitcher extends Component
{
    public function __construct(private readonly TenantContext $context) {}

    public function render(): View
    {
        $current = $this->context->isInstalled() ? $this->context->workspace() : null;

        return view('components.workspace-switcher', [
            'tenants' => $this->tenantsOf(Auth::user()),
            'current' => $current,
            'canCreateWorkspace' => $current !== null && Gate::allows('create', Workspace::class),
        ]);
    }

    /**
     * @return Collection<int, Tenant>
     */
    private function tenantsOf(?User $user): Collection
    {
        if ($user === null) {
            return new Collection;
        }

        return Tenant::query()
            ->whereHas('members', fn (Builder $query): Builder => $query->where('user_id', $user->id))
            ->with('activeWorkspaces')
            ->orderBy('name')
            ->get();
    }
}
