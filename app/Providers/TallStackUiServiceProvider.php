<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use TallStackUi\Customization\Customization;

/**
 * Paints the TallStackUI internals with the project palette.
 *
 * The package ships its own Tailwind colours, mostly greys. Every block below
 * replaces them with the primary, secondary and dark ramps, which is what keeps
 * the rendered markup free of off-palette classes. Each method owns one family
 * of components so the mapping stays readable.
 */
class TallStackUiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureFields();
        $this->configureFormChrome();
        $this->configureSlide();
        $this->configureSidebar();
        $this->configureSidebarMain();
        $this->configureSidebarItems();
        $this->configureHeader();
        $this->configureThemeSwitch();
        $this->configureDropdown();
        $this->configureSurfaces();
        $this->configureModal();
        $this->configureToast();
    }

    /**
     * Field colours that reach WCAG 2.2 AA against the project palette.
     *
     * The package defaults rest on grey-200 borders and a grey-400 placeholder,
     * and keep the primary-600 focus ring in dark mode. Measured against this
     * palette those give 1.24:1, 2.54:1 and 1.95:1, so the neutral steps take
     * over: 5.01:1 for the resting border, 5.01:1 for the placeholder and
     * 6.91:1 for the dark focus ring.
     */
    protected function configureFields(): void
    {
        app(Customization::class)
            ->form('input')
            ->block('input.wrapper', 'focus:ring-primary-600 focus-within:focus:ring-primary-600 focus-within:ring-primary-600 dark:focus-within:ring-primary-400 flex rounded-md ring-1 focus-within:ring-2')
            ->block('input.base', 'dark:placeholder-dark-400 w-full rounded-md border-0 bg-transparent py-1.5 ring-0 placeholder:text-dark-500 focus:outline-hidden focus:ring-transparent sm:text-sm sm:leading-6')
            ->block('input.color.base', 'dark:ring-dark-400 dark:text-dark-300 text-dark-900 ring-dark-500')
            ->block('input.slot', 'dark:text-dark-400 flex select-none items-center whitespace-nowrap text-dark-500 sm:text-sm');
    }

    /**
     * Labels, checkboxes, validation messages and the password rules.
     *
     * These all sit inside the form components, which are the last place grey
     * and the raw status colours were still reaching the browser: a grey-600
     * label, a grey-200 checkbox border, a grey-700 rules summary, a green-500
     * success tick and a red-500 message.
     *
     * The severity mapping follows the one used for the toast: errors take the
     * primary ramp, which on this palette is the darkest and reads as the most
     * severe, and success takes secondary.
     */
    protected function configureFormChrome(): void
    {
        $customization = app(Customization::class);

        $customization->form('label')
            ->block('text', 'dark:text-dark-300 mb-1 block text-sm font-medium text-dark-600')
            ->block('asterisk', 'font-bold text-primary-600 dark:text-primary-400 not-italic')
            ->block('error', 'text-primary-600 dark:text-primary-400');

        $customization->form('error')
            ->block('text', 'mt-1 block text-sm font-medium text-primary-600 dark:text-primary-400');

        $customization->form('checkbox')
            ->block('input.class', 'form-checkbox dark:border-dark-400 dark:bg-dark-800 rounded border-dark-500 bg-white ring-0 ring-offset-0 focus:ring-0 focus:ring-offset-0 focus:text-primary-600');

        $customization->form('password')
            ->block('icon.capslock', 'h-5 w-5 text-primary-600 dark:text-primary-400')
            ->block('rules.title', 'text-md font-semibold text-primary-600 dark:text-dark-300')
            ->block('rules.items.base', 'inline-flex items-center gap-1 text-dark-700 dark:text-dark-300 text-sm')
            ->block('rules.items.icons.error', 'h-5 w-5 text-primary-600 dark:text-primary-400')
            ->block('rules.items.icons.success', 'h-5 w-5 text-secondary-600 dark:text-secondary-400');

        // The checkbox and radio labels are rendered by the wrapper component.
        $customization->wrapper('radio')
            ->block('label.text', 'dark:text-dark-400 cursor-pointer items-center text-sm font-medium text-dark-700');
    }

    /**
     * Scrim shared by the slide, the modal and the sidebar.
     *
     * A grey scrim is outside the palette and washes out a light page, so the
     * overlay follows the neutral ramp instead.
     */
    protected function scrim(): string
    {
        return 'fixed inset-0 bg-dark-900/40 dark:bg-dark-950/75 transform transition-opacity';
    }

    /**
     * Slide, used for the mobile navigation.
     *
     * Drops the grey scrim, close icon, body text, footer and divider borders.
     */
    protected function configureSlide(): void
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
     * Sidebar, used by the dashboard and the settings pages.
     *
     * Its mobile backdrop was grey-900 at 80 percent and its collapse toggle a
     * grey-500 icon, both outside the palette.
     */
    protected function configureSidebar(): void
    {
        app(Customization::class)
            ->sideBar()
            ->block('mobile.backdrop', 'fixed inset-0 bg-dark-900/80 dark:bg-dark-950/50')
            ->block('mobile.footer', 'shrink-0 border-t border-dark-200 dark:border-dark-600 px-2 py-4')
            ->block('mobile.wrapper.sixth', 'flex flex-1 flex-col gap-y-1 px-2')
            ->block('desktop.footer', 'shrink-0 overflow-hidden border-t border-dark-200 dark:border-dark-700 px-2 pt-3')
            ->block('desktop.wrapper.fifth', 'flex flex-1 flex-col gap-y-1 px-2')
            ->block('desktop.wrapper.second', 'dark:bg-dark-800 dark:border-dark-700 flex grow flex-col border-r border-dark-200 bg-white pb-4 transition-[width] duration-300');
    }

    /**
     * Sidebar main area, used by layouts/sidebar.blade.php.
     *
     * The package main is `mx-auto w-full max-w-full p-10`: full bleed with 40px
     * of padding on top of the padding the layout adds itself, which pushed the
     * content wider and further from the edge than every other layout. It now
     * carries the same box as components/shell.blade.php.
     */
    protected function configureSidebarMain(): void
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
    protected function configureSidebarItems(): void
    {
        app(Customization::class)
            ->sideBar('item')
            ->block('item.state.base', 'group flex items-center rounded-md p-2 text-sm font-medium transition-all')
            ->block('item.state.normal', 'text-dark-600 hover:bg-dark-800/5 hover:text-dark-900 dark:text-dark-300 dark:hover:bg-white/[7%] dark:hover:text-white')
            ->block('item.state.current', 'bg-dark-800/5 text-dark-900 dark:bg-white/10 dark:text-white')
            ->block('item.icon', 'h-5 w-5 shrink-0 text-dark-500 transition-all dark:text-dark-400')
            ->block('group.button', 'flex w-full cursor-pointer items-center rounded-md p-2 text-left text-sm font-medium text-dark-600 transition-all hover:bg-dark-800/5 hover:text-dark-900 dark:text-white dark:hover:bg-white/10')
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
    protected function configureHeader(): void
    {
        app(Customization::class)
            ->layout('header')
            ->block('wrapper.base', 'dark:bg-dark-800 dark:border-dark-700 sticky top-0 z-40 flex shrink-0 items-center gap-x-4 border-b border-dark-200 bg-white px-4 sm:gap-x-6 sm:px-6 lg:px-8 tsui-scrollbar-bleed')
            ->block('button.icon.size', 'h-6 w-6 text-dark-500 dark:text-dark-300')
            ->block('collapse.icon.size', 'h-6 w-6 text-dark-500 dark:text-dark-300')
            ->block('slots.wrapper', 'mx-auto flex w-full max-w-7xl flex-1 items-center');
    }

    /**
     * Theme switch, used on the settings pages.
     *
     * The segmented variation paints the sun blue and the moon yellow, and sits
     * on a grey track with grey inactive labels. All of it moves to the neutral
     * ramp; the icons keep enough separation through their own weights.
     */
    protected function configureThemeSwitch(): void
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

    /**
     * Dropdown items, used by the user menu.
     *
     * Resting text, hover fill, focus fill, separator border and icons all move
     * from grey onto the neutral ramp.
     */
    protected function configureDropdown(): void
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
     * Cards and accordions, used across the landing page and the settings pages.
     *
     * Both ship grey-200 borders, grey-700 body text and a grey-50 hover, all
     * outside the palette, so they move onto the neutral ramp.
     */
    protected function configureSurfaces(): void
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
     * Modal, used by the two-factor and delete-account dialogs.
     */
    protected function configureModal(): void
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
    protected function configureToast(): void
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
