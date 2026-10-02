<x-layouts::card :title="__('Action not completed')">
    <div class="flex flex-col gap-4 text-center" data-test="domain-error">
        <h1 class="text-xl font-semibold tracking-tight text-dark-900 dark:text-white">
            {{ __('Action not completed') }}
        </h1>

        <p class="text-sm text-dark-600 dark:text-dark-300">{{ $message }}</p>

        <p class="text-sm text-dark-500 dark:text-dark-400">
            {{ $retryable ? __('You can try again in a moment.') : __('Trying again will not change the result.') }}
        </p>

        <p class="text-xs text-dark-500 dark:text-dark-400">
            {{ __('Reference: :reference', ['reference' => $reference]) }}
        </p>

        <div>
            <x-button :href="url()->previous()" :text="__('Back')" flat />
        </div>
    </div>
</x-layouts::card>
