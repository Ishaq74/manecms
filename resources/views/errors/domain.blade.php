<x-layouts::auth :title="__('Action not completed')">
    <div class="flex flex-col items-center gap-4 text-center" data-test="domain-error">
        <x-mane::page-header align="center" :title="__('Action not completed')" :description="$message" />

        <p class="text-sm text-fg-muted">
            {{ $retryable ? __('You can try again in a moment.') : __('Trying again will not change the result.') }}
        </p>

        <p class="font-mono text-xs text-fg-muted">
            {{ __('Reference: :reference', ['reference' => $reference]) }}
        </p>

        <x-mane::button variant="ghost" icon="arrow-left" class="[&_svg]:rtl:rotate-180" :href="url()->previous()" :text="__('Back')" />
    </div>
</x-layouts::auth>
