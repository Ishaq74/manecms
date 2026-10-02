<?php

namespace App\View\Components\TallStackUi\Customizations;

use TallStackUi\Customization\Customization;

/**
 * Overlays: the slide, the modal, their shared scrim and the toast.
 *
 * Paints TallStackUI with the project palette; see App\Providers\TallStackUiServiceProvider.
 */
final class OverlayCustomization
{
    public function __invoke(): void
    {
        $this->configureSlide();
        $this->configureModal();
        $this->configureToast();
    }

    /**
     * Scrim shared by the slide, the modal and the sidebar.
     *
     * A grey scrim is outside the palette and washes out a light page, so the
     * overlay follows the neutral ramp instead.
     */
    private function scrim(): string
    {
        return 'fixed inset-0 bg-dark-900/40 dark:bg-dark-950/75 transform transition-opacity';
    }

    /**
     * Slide, used for the mobile navigation.
     *
     * Drops the grey scrim, close icon, body text, footer and divider borders.
     */
    private function configureSlide(): void
    {
        app(Customization::class)
            ->slide()
            ->block('wrapper.first', $this->scrim())
            ->block('title.text', 'whitespace-normal font-medium text-md text-dark-600 dark:text-dark-300')
            ->block('title.close', 'h-5 w-5 cursor-pointer text-dark-400 dark:text-dark-500')
            ->block('body', 'soft-scrollbar dark:text-dark-300 grow overflow-y-auto rounded-b-xl px-6 py-5 text-dark-600')
            ->block('footer.wrapper', 'border-t border-t-dark-200 px-4 pt-4 dark:border-t-dark-600')
            ->block('header.divider', 'border-b border-b-dark-200 pb-4 dark:border-b-dark-600');
    }

    /**
     * Modal, used by the two-factor and delete-account dialogs.
     */
    private function configureModal(): void
    {
        app(Customization::class)
            ->modal()
            ->block('wrapper.first', $this->scrim())
            ->block('handle.bar', 'h-1 w-10 rounded-full bg-dark-300 dark:bg-dark-500')
            ->block('title.wrapper', 'dark:border-b-dark-700 flex items-center justify-between border-b border-b-dark-200 px-4 py-2.5')
            ->block('title.text', 'text-md text-dark-600 dark:text-dark-300 whitespace-normal font-medium')
            ->block('title.close', 'text-dark-400 dark:text-dark-500 h-5 w-5 cursor-pointer')
            ->block('body', 'dark:text-dark-300 grow rounded-b-xl py-5 text-dark-600 px-4')
            ->block('footer.wrapper', 'dark:text-dark-300 dark:border-t-dark-700 rounded-b-xl border-t border-t-dark-200 p-4 text-dark-600');
    }

    /**
     * Toast chrome.
     *
     * The five toast types are mapped onto the palette by ToastColors; what
     * remains here is the surrounding chrome, which still referenced grey-800
     * for the title, grey-700 for the description, grey-400 for the close and
     * expand buttons and grey-100 for the progress track.
     */
    private function configureToast(): void
    {
        app(Customization::class)
            ->toast()
            ->block('content.text', 'dark:text-dark-200 text-sm font-medium text-dark-800')
            ->block('content.description', 'dark:text-dark-300 mt-1 text-sm text-dark-600')
            ->block('buttons.close.class', 'inline-flex text-dark-500 focus:outline-hidden focus:ring-0 cursor-pointer')
            ->block('buttons.expand.class', 'inline-flex text-dark-500 focus:outline-hidden focus:ring-0')
            ->block('progress.wrapper', 'dark:bg-dark-700 relative h-1 w-full rounded-full bg-dark-200');
    }
}
