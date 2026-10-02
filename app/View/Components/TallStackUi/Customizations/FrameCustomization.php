<?php

namespace App\View\Components\TallStackUi\Customizations;

use TallStackUi\Customization\Customization;

/**
 * The application frame: sidebar, its items, the layout header and main area.
 *
 * Paints TallStackUI with the project palette; see App\Providers\TallStackUiServiceProvider.
 */
final class FrameCustomization
{
    public function __invoke(): void
    {
        $this->configureSidebar();
        $this->configureSidebarMain();
        $this->configureSidebarItems();
        $this->configureHeader();
    }

    /**
     * Sidebar, used by the dashboard and the settings pages.
     *
     * Its mobile backdrop was grey-900 at 80 percent and its collapse toggle a
     * grey-500 icon, both outside the palette.
     */
    private function configureSidebar(): void
    {
        app(Customization::class)
            ->sideBar()
            ->block('mobile.backdrop', 'fixed inset-0 bg-dark-900/80 dark:bg-dark-950/50')
            ->block('mobile.footer', 'shrink-0 border-t border-dark-200 dark:border-dark-600 px-2 py-4')
            ->block('mobile.wrapper.sixth', 'flex flex-1 flex-col gap-y-1 px-2')
            ->block('desktop.footer', 'shrink-0 overflow-hidden border-t border-dark-200 dark:border-dark-700 px-2 pt-3')
            ->block('desktop.wrapper.fifth', 'flex flex-1 flex-col gap-y-1 px-2')
            ->block('desktop.wrapper.second', 'dark:bg-dark-800 dark:border-dark-700 flex grow flex-col border-e border-dark-200 bg-white pb-4 transition-[width] duration-300');
    }

    /**
     * Sidebar main area, used by layouts/sidebar.blade.php.
     *
     * The package main is `mx-auto w-full max-w-full p-10`: full bleed with 40px
     * of padding on top of the padding the layout adds itself, which pushed the
     * content wider and further from the edge than every other layout. It now
     * carries the same box as components/shell.blade.php.
     */
    private function configureSidebarMain(): void
    {
        app(Customization::class)
            ->layout()
            ->block('main', 'mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8');
    }

    /**
     * Sidebar items and separator, used by layouts/sidebar.blade.php.
     *
     * The package shipped labels in primary-500 at font-semibold with 24px icons
     * and a text-base separator, one step louder than the top navigation, and the
     * collapsed flyout header kept a grey-500 that is outside the palette. The
     * navigation ramp and the 20px icon of the header are used instead.
     */
    private function configureSidebarItems(): void
    {
        app(Customization::class)
            ->sideBar('item')
            ->block('item.state.base', 'group flex items-center rounded-md p-2 text-sm font-medium transition-all')
            ->block('item.state.normal', 'text-dark-600 hover:bg-dark-800/5 hover:text-dark-900 dark:text-dark-300 dark:hover:bg-white/[7%] dark:hover:text-white')
            ->block('item.state.current', 'bg-dark-800/5 text-dark-900 dark:bg-white/10 dark:text-white')
            ->block('item.icon', 'h-5 w-5 shrink-0 text-dark-500 transition-all dark:text-dark-400')
            ->block('group.button', 'flex w-full cursor-pointer items-center rounded-md p-2 text-start text-sm font-medium text-dark-600 transition-all hover:bg-dark-800/5 hover:text-dark-900 dark:text-white dark:hover:bg-white/10')
            ->block('group.icon.base', 'h-5 w-5 shrink-0 text-dark-500 dark:text-dark-400')
            ->block('group.flyout.header', 'sticky top-0 -mx-2 bg-white px-2 pb-1 pt-2 text-xs font-semibold uppercase tracking-wide text-dark-500 dark:bg-dark-800 dark:text-dark-300')
            ->and()
            ->sideBar('separator')
            ->block('simple.base', 'text-sm font-semibold leading-6 text-dark-600 whitespace-nowrap overflow-hidden transition-all duration-150 dark:text-dark-100')
            ->block('line.border', 'w-full border-t border-dark-200 dark:border-dark-700')
            ->block('line.base', 'bg-white px-3 text-xs font-semibold uppercase tracking-wide text-dark-500 whitespace-nowrap overflow-hidden transition-all duration-150 dark:bg-dark-800 dark:text-dark-300');
    }

    /**
     * Layout header.
     *
     * The sidebar collapse toggle was a grey-500 icon on both the button and the
     * header slot wrapper. The slot wrapper also borrows the max-w-7xl of the shell
     * header, so the header content lines up with the content below it.
     */
    private function configureHeader(): void
    {
        app(Customization::class)
            ->layout('header')
            ->block('wrapper.base', 'dark:bg-dark-800 dark:border-dark-700 sticky top-0 z-40 flex shrink-0 items-center gap-x-4 border-b border-dark-200 bg-white px-4 sm:gap-x-6 sm:px-6 lg:px-8 tsui-scrollbar-bleed')
            ->block('button.icon.size', 'h-6 w-6 text-dark-500 dark:text-dark-300')
            ->block('collapse.icon.size', 'h-6 w-6 text-dark-500 dark:text-dark-300')
            ->block('slots.wrapper', 'mx-auto flex w-full max-w-7xl flex-1 items-center');
    }
}
