<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts::sidebar')] #[Title('Appearance settings')] class extends Component {
    //
}; ?>

<x-pages::settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
    <div class="max-w-md">
        <x-mane::theme-switch />
    </div>
</x-pages::settings.layout>
