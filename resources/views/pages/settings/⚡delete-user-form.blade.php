<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <h2 class="text-lg font-medium tracking-tight text-zinc-800 dark:text-white">
            {{ __('Delete account') }}
        </h2>

        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Delete your account and all of its resources') }}
        </p>
    </div>

    <x-button
        color="red"
        data-test="delete-user-button"
        :text="__('Delete account')"
        x-on:click="$tsui.open.modal('confirm-user-deletion')"
    />

    <livewire:pages::settings.delete-user-modal />
</section>
