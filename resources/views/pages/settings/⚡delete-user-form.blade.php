<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="flex flex-col items-start gap-4">
    <x-mane::section-header :title="__('Delete account')" :description="__('Delete your account and all of its resources')" />

    <x-mane::alert tone="danger" :text="__('This cannot be undone.')" />

    <x-mane::button
        variant="danger"
        icon="trash"
        data-test="delete-user-button"
        :text="__('Delete account')"
        x-on:click="$tsui.open.modal('confirm-user-deletion')"
    />

    <livewire:pages::settings.delete-user-modal />
</section>
