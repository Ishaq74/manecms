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

<div class="flex h-full w-full flex-1 flex-col gap-8">
    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-semibold tracking-tight text-dark-900 dark:text-white" data-test="workspace-name">
            {{ $this->workspaceName }}
        </h1>

        <p class="text-sm text-dark-500 dark:text-dark-400">
            {{ __('Welcome back, :name', ['name' => $this->firstName]) }}
        </p>
    </div>

    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div class="relative aspect-video overflow-hidden rounded-xl border border-dark-200 dark:border-dark-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-dark-900/20 dark:stroke-dark-100/20" />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-dark-200 dark:border-dark-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-dark-900/20 dark:stroke-dark-100/20" />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-dark-200 dark:border-dark-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-dark-900/20 dark:stroke-dark-100/20" />
            </div>
        </div>
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-dark-200 dark:border-dark-700">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-dark-900/20 dark:stroke-dark-100/20" />
        </div>
    </div>
</div>
