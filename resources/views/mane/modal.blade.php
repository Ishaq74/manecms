{{--
    TallStackUI points a titled dialog at a missing "modal-title" id and leaves its close
    button unnamed; ManeUI names both. Livewire morphs the dialog back to the server markup
    on every update, so an observer re-applies the names. The label is an element, so a
    title that Livewire re-renders keeps naming the dialog.
--}}
@props([
    'id',
    'title' => null,
    'size' => 'lg',
    'wire' => null,
    'persistent' => false,
])

<x-ts-modal {{ $attributes }} :id="$id" :title="$title" :size="$size" :wire="$wire" :persistent="$persistent">
    @if ($title !== null)
        <span
            id="{{ $id }}-label"
            hidden
            x-init="
                const dialog = $el.closest('[role=dialog]');
                const name = () => {
                    if (dialog.getAttribute('aria-labelledby') !== $el.id) {
                        dialog.setAttribute('aria-labelledby', $el.id);
                    }

                    const close = dialog.querySelector('[dusk=tallstackui_modal_close]');

                    if (close && ! close.hasAttribute('aria-label')) {
                        close.setAttribute('aria-label', @js(__('Close')));
                    }
                };

                if (dialog) {
                    name();
                    new MutationObserver(name).observe(dialog, { attributes: true, subtree: true, attributeFilter: ['aria-labelledby', 'aria-label'] });
                }
            "
        >{{ $title }}</span>
    @endif

    {{ $slot }}

    @isset($footer)
        <x-slot:footer>{{ $footer }}</x-slot:footer>
    @endisset
</x-ts-modal>
