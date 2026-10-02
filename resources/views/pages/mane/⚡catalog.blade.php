<?php

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

new #[Layout('layouts::sidebar')] #[Title('ManeUI catalogue')] class extends Component {
    use Interactions, WithPagination;

    public string $demoName = '';

    public string $demoEmail = 'not-an-address';

    public string $demoNotes = '';

    public string $demoRole = '';

    public bool $demoAccepted = false;

    public bool $demoNotifications = true;

    public string $demoPlan = 'starter';

    public int $submissions = 0;

    public function mount(): void
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $this->addError('demoEmail', __('This email address is not valid.'));
    }

    /**
     * Target of the §407 demo: its counter must never move while the submit is disabled.
     */
    public function submitDemo(): void
    {
        $this->submissions++;
    }

    public function sendToast(): void
    {
        $this->toast()->success(__('Changes saved.'))->send();
    }

    /**
     * @return LengthAwarePaginator<int, int>
     */
    #[Computed]
    public function demoPaginator(): LengthAwarePaginator
    {
        $page = $this->getPage();

        return new LengthAwarePaginator(range(($page - 1) * 10 + 1, $page * 10), 120, 10, $page);
    }
}; ?>

<div
    class="flex w-full flex-col gap-10"
    x-data="{ density: 'comfortable', direction: document.documentElement.dir || 'ltr' }"
    x-bind:data-density="density"
    data-test="mane-catalog"
>
    <header class="flex flex-col gap-4">
        <x-mane::page-header :title="__('ManeUI catalogue')" :description="__('Every ManeUI component and its states. Available in local development only.')" />

        <div class="flex flex-wrap items-end gap-3">
            <x-mane::button
                variant="secondary"
                size="sm"
                icon="arrows-right-left"
                x-on:click="direction = direction === 'rtl' ? 'ltr' : 'rtl'; document.documentElement.dir = direction"
                :text="__('Toggle direction')"
                data-test="catalog-toggle-direction"
            />

            <div class="w-48">
                <x-mane::select
                    id="catalog-density"
                    x-model="density"
                    :label="__('Density')"
                    :options="['comfortable' => __('Comfortable'), 'compact' => __('Compact'), 'dense' => __('Dense')]"
                />
            </div>

            <div class="w-72">
                <x-mane::theme-switch />
            </div>
        </div>
    </header>

    <section aria-labelledby="catalog-actions" class="flex flex-col gap-4">
        <h2 id="catalog-actions" class="text-lg font-semibold text-fg">{{ __('Actions') }}</h2>

        <div class="flex flex-wrap items-center gap-3">
            <x-mane::button :text="__('Primary')" />
            <x-mane::button variant="secondary" :text="__('Secondary')" />
            <x-mane::button variant="ghost" :text="__('Ghost')" />
            <x-mane::button variant="danger" :text="__('Danger')" />
            <x-mane::button icon="plus" :text="__('With icon')" />
            <x-mane::button size="sm" :text="__('Small')" />
            <x-mane::button size="lg" :text="__('Large')" />
            <x-mane::button disabled :text="__('Disabled')" />
            <x-mane::icon-button icon="pencil-square" :label="__('Edit')" />
            <x-mane::icon-button icon="trash" variant="danger" :label="__('Delete')" />
            <x-mane::link href="#catalog-actions" :text="__('Link')" />
        </div>

        <div class="flex flex-wrap items-center gap-3 text-fg-muted">
            <x-mane::icon name="bell" class="size-5" />
            <x-mane::icon name="calendar-days" class="size-5" />
            <x-mane::icon name="exclamation-triangle" class="size-5 text-danger" :label="__('Warning')" />
        </div>
    </section>

    <section aria-labelledby="catalog-disabled" class="flex flex-col gap-4">
        <h2 id="catalog-disabled" class="text-lg font-semibold text-fg">{{ __('Disabled submit (§407)') }}</h2>

        <x-mane::form wire:submit="submitDemo" :dirty-notice="false" data-test="catalog-disabled-form">
            <x-mane::input wire:model="demoName" :label="__('Name')" data-test="catalog-disabled-input" />

            <p class="text-sm text-fg-muted" data-test="catalog-submissions">{{ __('Submissions: :count', ['count' => $submissions]) }}</p>

            <x-slot:actions>
                <x-mane::button type="submit" disabled :text="__('Submit')" data-test="catalog-disabled-submit" />
            </x-slot:actions>
        </x-mane::form>
    </section>

    <section aria-labelledby="catalog-fields" class="flex flex-col gap-4">
        <h2 id="catalog-fields" class="text-lg font-semibold text-fg">{{ __('Fields') }}</h2>

        <div class="grid gap-6 md:grid-cols-2">
            <x-mane::input wire:model="demoName" :label="__('Name')" :hint="__('As it should appear on invoices.')" />
            <x-mane::input wire:model="demoEmail" :label="__('Email address')" type="email" />
            <x-mane::input :label="__('Read only')" value="ACME-2026" readonly />
            <x-mane::input :label="__('Disabled')" value="—" disabled />
            <x-mane::password :label="__('Password')" />
            <x-mane::textarea wire:model="demoNotes" :label="__('Notes')" count maxlength="200" />
            <x-mane::select wire:model="demoRole" :label="__('Role')" :placeholder="__('Choose a role')" :options="['owner' => __('Owner'), 'admin' => __('Admin'), 'member' => __('Member')]" />
            <div class="flex flex-col gap-3">
                <x-mane::checkbox wire:model="demoAccepted" :label="__('I accept the terms')" />
                <x-mane::switch wire:model="demoNotifications" :label="__('Email notifications')" />
                <x-mane::radio wire:model="demoPlan" value="starter" :label="__('Starter')" />
                <x-mane::radio wire:model="demoPlan" value="business" :label="__('Business')" />
            </div>
            <x-mane::pin :label="__('One-time code')" />
            <x-mane::otp-input name="catalog_code" :label="__('Authentication code')" />
        </div>
    </section>

    <section aria-labelledby="catalog-feedback" class="flex flex-col gap-4">
        <h2 id="catalog-feedback" class="text-lg font-semibold text-fg">{{ __('Feedback') }}</h2>

        <div class="flex flex-wrap items-center gap-3">
            <x-mane::badge :text="__('Primary')" />
            <x-mane::badge variant="secondary" :text="__('Secondary')" />
            <x-mane::badge variant="muted" :text="__('Muted')" />
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <x-mane::status tone="success" :text="__('Paid')" />
            <x-mane::status tone="warning" :text="__('Overdue soon')" />
            <x-mane::status tone="danger" :text="__('Overdue')" />
            <x-mane::status tone="info" :text="__('Draft')" />
            <x-mane::status tone="muted" :text="__('Archived')" />
        </div>

        <div class="grid gap-3 md:grid-cols-2">
            <x-mane::alert tone="info" :title="__('Information')" :text="__('Your trial ends in 12 days.')" />
            <x-mane::alert tone="success" :title="__('Success')" :text="__('The import finished.')" dismissible />
            <x-mane::alert tone="warning" :title="__('Warning')" :text="__('Two invoices are still unpaid.')" />
            <x-mane::alert tone="danger" :title="__('Error')" :text="__('The payment was declined.')" />
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <x-mane::button variant="secondary" wire:click="sendToast" loading="sendToast" :text="__('Show a toast')" data-test="catalog-toast" />

            <x-mane::tooltip :text="__('Shown on hover and on focus')">
                <x-mane::button variant="ghost" :text="__('Tooltip')" />
            </x-mane::tooltip>

            <x-mane::popover :label="__('Popover')">
                <p>{{ __('A popover holds a short form or details.') }}</p>
            </x-mane::popover>

            <x-mane::dropdown :label="__('Dropdown')" icon="chevron-down">
                <x-mane::dropdown.group :label="__('Document')">
                    <x-mane::dropdown.item icon="eye" :text="__('Preview')" />
                </x-mane::dropdown.group>
                <x-mane::dropdown.item icon="pencil-square" :text="__('Edit')" />
                <x-mane::dropdown.item icon="document-duplicate" :text="__('Duplicate')" />
                <x-mane::dropdown.item icon="trash" separator :text="__('Delete')" />
            </x-mane::dropdown>
        </div>
    </section>

    <section aria-labelledby="catalog-overlays" class="flex flex-col gap-4">
        <h2 id="catalog-overlays" class="text-lg font-semibold text-fg">{{ __('Overlays') }}</h2>

        <div class="flex flex-wrap items-center gap-3">
            <x-mane::button variant="secondary" x-on:click="$tsui.open.modal('catalog-modal')" :text="__('Open the modal')" data-test="catalog-open-modal" />
            <x-mane::button variant="secondary" x-on:click="$tsui.open.slide('catalog-drawer')" :text="__('Open the drawer')" data-test="catalog-open-drawer" />
        </div>

        <x-mane::modal id="catalog-modal" :title="__('Modal')">
            <p class="text-sm text-fg-muted">{{ __('Escape closes it and focus returns to the trigger.') }}</p>

            <x-slot:footer>
                <x-mane::button variant="ghost" x-on:click="$tsui.close.modal('catalog-modal')" :text="__('Close')" data-test="catalog-close-modal" />
            </x-slot:footer>
        </x-mane::modal>

        <x-mane::drawer id="catalog-drawer" :title="__('Drawer')" side="end">
            <p class="text-sm text-fg-muted">{{ __('A drawer opens from the end side, which follows the writing direction.') }}</p>
        </x-mane::drawer>
    </section>

    <section aria-labelledby="catalog-navigation" class="flex flex-col gap-4">
        <h2 id="catalog-navigation" class="text-lg font-semibold text-fg">{{ __('Navigation') }}</h2>

        <x-mane::breadcrumb :items="[['label' => __('Home'), 'href' => route('home')], ['label' => __('Settings'), 'href' => '#catalog-navigation'], ['label' => __('Profile')]]" />

        <x-mane::tabs selected="general">
            <x-mane::tabs.item tab="general" :title="__('General')">
                <p class="text-sm text-fg-muted">{{ __('General settings.') }}</p>
            </x-mane::tabs.item>
            <x-mane::tabs.item tab="billing" :title="__('Billing')">
                <p class="text-sm text-fg-muted">{{ __('Billing settings.') }}</p>
            </x-mane::tabs.item>
        </x-mane::tabs>

        <x-mane::accordion>
            <x-mane::accordion.item :title="__('What is a workspace?')" id="catalog-faq-1">
                <p class="text-sm">{{ __('A workspace separates the content and activity of a team, brand or project.') }}</p>
            </x-mane::accordion.item>
            <x-mane::accordion.item :title="__('Can I archive one?')" id="catalog-faq-2">
                <p class="text-sm">{{ __('Yes, and its data is kept.') }}</p>
            </x-mane::accordion.item>
        </x-mane::accordion>

        <x-mane::pagination :paginator="$this->demoPaginator" />
    </section>

    <section aria-labelledby="catalog-headings" class="flex flex-col gap-4">
        <h2 id="catalog-headings" class="text-lg font-semibold text-fg">{{ __('Headings') }}</h2>

        <x-mane::section-header :level="3" :title="__('Billing details')" :description="__('Used on invoices and receipts.')" />

        <x-mane::divider />
        <x-mane::divider :label="__('Or continue with email')" />

        <nav aria-label="{{ __('Navigation') }}" class="flex flex-wrap gap-1">
            <x-mane::nav-link href="#catalog-headings" icon="user-circle" current>{{ __('Profile') }}</x-mane::nav-link>
            <x-mane::nav-link href="#catalog-headings" icon="shield-check">{{ __('Security') }}</x-mane::nav-link>
        </nav>
    </section>

    <section aria-labelledby="catalog-surfaces" class="flex flex-col gap-4">
        <h2 id="catalog-surfaces" class="text-lg font-semibold text-fg">{{ __('Surfaces') }}</h2>

        <div class="grid gap-4 md:grid-cols-2">
            <x-mane::card>
                <x-slot:header>
                    <p class="font-semibold text-fg">{{ __('Card') }}</p>
                </x-slot:header>

                <div class="flex items-center gap-3">
                    <x-mane::avatar :text="'AM'" />
                    <x-mane::avatar :text="'JD'" size="sm" />
                    <p class="text-sm">{{ __('A card groups related content.') }}</p>
                </div>
            </x-mane::card>

            <x-mane::card>
                <x-mane::skeleton :lines="4" />
            </x-mane::card>
        </div>
    </section>

    <section aria-labelledby="catalog-patterns" class="flex flex-col gap-4">
        <h2 id="catalog-patterns" class="text-lg font-semibold text-fg">{{ __('Editorial patterns') }}</h2>

        <div class="grid gap-4 md:grid-cols-3">
            <x-mane::feature icon="bolt" :title="__('Automation')">{{ __('Workflows run on events, with approvals.') }}</x-mane::feature>
            <x-mane::card><x-mane::stat value="128" :label="__('Active members')" delta="▲ 12" /></x-mane::card>
            <ul class="grid gap-2">
                <x-mane::check-item :title="__('Isolated')">{{ __('per tenant, down to the database.') }}</x-mane::check-item>
                <x-mane::check-item :title="__('Audited')">{{ __('every sensitive change.') }}</x-mane::check-item>
            </ul>
        </div>

        <x-mane::section class="rounded-surface" tone="sunken" :eyebrow="__('Kicker')" :title="__('A display title')" :lead="__('A lead paragraph that explains the section.')" />
    </section>

    <section aria-labelledby="catalog-states" class="flex flex-col gap-4">
        <h2 id="catalog-states" class="text-lg font-semibold text-fg">{{ __('Screen states') }}</h2>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach (['empty', 'first-use', 'no-access', 'no-results', 'error'] as $kind)
                <x-mane::empty-state :kind="$kind" wire:key="empty-{{ $kind }}" />
            @endforeach
        </div>

        <x-mane::error-state
            :title="__('The report could not be generated')"
            :description="__('The data source did not answer in time. Your filters are kept.')"
            reference="01J9Z3Q4M8K2"
        >
            <x-slot:action>
                <x-mane::button variant="secondary" size="sm" icon="arrow-path" :text="__('Try again')" />
            </x-slot:action>
        </x-mane::error-state>

        <div class="flex flex-col gap-2">
            @foreach (['initial', 'action', 'background', 'queued'] as $kind)
                <x-mane::loading-state :kind="$kind" wire:key="loading-{{ $kind }}" />
            @endforeach
        </div>

        <x-mane::spinner />
    </section>
</div>
