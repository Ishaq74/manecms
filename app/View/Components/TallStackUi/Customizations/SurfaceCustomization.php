<?php

namespace App\View\Components\TallStackUi\Customizations;

use TallStackUi\Customization\Customization;

/**
 * Surfaces and inline controls: cards, accordions, tabs, dropdown items and the theme switch.
 *
 * Paints TallStackUI with the project palette; see App\Providers\TallStackUiServiceProvider.
 */
final class SurfaceCustomization
{
    public function __invoke(): void
    {
        $this->configureSurfaces();
        $this->configureTabs();
        $this->configureDropdown();
        $this->configureThemeSwitch();
    }

    /**
     * Cards and accordions, used across the landing page and the settings pages.
     *
     * Both ship grey-200 borders, grey-700 body text and a grey-50 hover, all
     * outside the palette, so they move onto the neutral ramp.
     */
    private function configureSurfaces(): void
    {
        app(Customization::class)
            ->card()
            ->block('bordered', 'border border-dark-200 dark:border-dark-700')
            ->block('body', 'grow px-6 py-6 text-dark-600 dark:text-dark-300')
            ->and()
            ->accordion()
            ->block('bordered', 'border border-dark-200 dark:border-dark-700')
            ->and()
            ->accordion('items')
            ->block('item.wrapper', 'border-b border-dark-200 last:border-b-0 dark:border-dark-700')
            ->block('item.trigger.base', 'flex w-full cursor-pointer items-center gap-3 px-4 py-3 text-start text-sm font-medium justify-between transition-colors hover:bg-dark-100 dark:hover:bg-dark-700')
            ->block('item.trigger.closed', 'text-dark-800 dark:text-dark-200')
            ->block('item.content', 'px-4 pb-4 text-sm text-dark-500 dark:text-dark-400');
    }

    /**
     * Tabs, shown in the ManeUI catalogue.
     *
     * The package paints the tab strip grey-50 and the inactive tabs grey-400,
     * which measures 2.48:1: the neutral ramp takes over, with dark-600 for the
     * inactive labels (5.6:1) and the primary ramp for the selected one.
     */
    private function configureTabs(): void
    {
        app(Customization::class)
            ->tab()
            ->block('base.body', 'soft-scrollbar flex-nowrap overflow-auto flex bg-dark-50 dark:bg-dark-900 rounded-t-lg')
            ->block('base.content', 'text-dark-700 dark:text-dark-300 p-4')
            ->block('base.divider', 'h-px border-0 bg-dark-200 dark:bg-dark-700')
            ->block('bordered', 'border border-dark-200 dark:border-dark-700')
            ->block('item.select', 'text-primary-600 dark:text-primary-400 border-primary-600 dark:border-primary-400 group inline-flex cursor-pointer items-center border-b-2 font-medium')
            ->block('item.unselect', 'text-dark-600 dark:text-dark-400 cursor-pointer border-b-2 border-transparent font-medium flex');
    }

    /**
     * Dropdown items, used by the user menu.
     *
     * Resting text, hover fill, focus fill, separator border and icons all move
     * from grey onto the neutral ramp.
     */
    private function configureDropdown(): void
    {
        app(Customization::class)
            ->dropdown('items')
            ->block('item.base', 'text-dark-600 dark:text-dark-300 dark:hover:bg-dark-700 dark:focus:bg-dark-700 flex w-full cursor-pointer items-center whitespace-nowrap transition-colors duration-150 hover:bg-dark-100 focus:bg-dark-100 focus:outline-hidden')
            ->block('border', 'dark:border-t-dark-700 border-t border-t-dark-200')
            ->block('icon.base', 'dark:text-dark-300 text-dark-500');

        // The settings navigation is rendered as dropdown submenu items, which
        // carry the same grey defaults as the plain items.
        app(Customization::class)
            ->dropdown('submenu')
            ->block('item.base', 'text-dark-600 dark:text-dark-300 dark:hover:bg-dark-700 dark:focus:bg-dark-700 flex w-full cursor-pointer items-center whitespace-nowrap transition-colors duration-150 hover:bg-dark-100 focus:bg-dark-100 focus:outline-hidden')
            ->block('border', 'dark:border-t-dark-700 border-t border-t-dark-200');
    }

    /**
     * Theme switch, used on the settings pages.
     *
     * The segmented variation paints the sun blue and the moon yellow, and sits
     * on a grey track with grey inactive labels. All of it moves to the neutral
     * ramp; the icons keep enough separation through their own weights.
     */
    private function configureThemeSwitch(): void
    {
        app(Customization::class)
            ->themeSwitch()
            ->block('colors.moon', 'text-dark-500 dark:text-dark-400')
            ->block('colors.sun', 'text-dark-500 dark:text-dark-400')
            ->block('segmented.wrapper', 'dark:bg-dark-900 inline-flex items-center gap-1 rounded-lg bg-dark-200 p-1 w-full')
            ->block('segmented.inactive', 'dark:text-dark-300 dark:hover:text-dark-100 text-dark-600 hover:text-dark-900')
            ->block('segmented.colors.moon', 'text-dark-500 dark:text-dark-400')
            ->block('segmented.colors.sun', 'text-dark-500 dark:text-dark-400')
            ->block('segmented.colors.system', 'dark:text-white text-dark-600')
            ->block('switch.off', 'bg-dark-300 dark:bg-dark-700');
    }
}
