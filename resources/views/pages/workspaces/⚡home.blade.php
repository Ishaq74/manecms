<?php

use App\Domain\Tenancy\Context\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::sidebar')] #[Title('Dashboard')] class extends Component {
    #[Computed]
    public function workspaceName(): string
    {
        return app(TenantContext::class)->workspace()->name;
    }

    #[Computed]
    public function firstName(): string
    {
        return Str::of((string) Auth::user()?->name)->before(' ')->toString();
    }
}; ?>

<div class="flex w-full flex-col gap-8">
    <x-mane::page-header :title="$this->workspaceName" :description="__('Welcome back, :name', ['name' => $this->firstName])" data-test="workspace-name" />

    <x-mane::card>
        <x-mane::empty-state
            kind="first-use"
            :title="__('This workspace is ready')"
            :description="__('Modules will add their activity, figures and shortcuts here.')"
        >
            @can('update', app(App\Domain\Tenancy\Context\TenantContext::class)->workspace())
                <x-slot:action>
                    <x-mane::button
                        variant="secondary"
                        icon="cog-6-tooth"
                        :href="route('workspace.settings', app(App\Domain\Tenancy\Context\TenantContext::class)->workspace())"
                        navigate
                        :text="__('Workspace settings')"
                    />
                </x-slot:action>
            @endcan
        </x-mane::empty-state>
    </x-mane::card>
</div>
