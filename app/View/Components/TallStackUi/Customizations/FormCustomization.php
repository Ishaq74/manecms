<?php

namespace App\View\Components\TallStackUi\Customizations;

use TallStackUi\Customization\Customization;

/**
 * Form fields, labels, checkboxes, validation messages and password rules.
 *
 * Paints TallStackUI with the project palette; see App\Providers\TallStackUiServiceProvider.
 */
final class FormCustomization
{
    public function __invoke(): void
    {
        $this->configureFields();
        $this->configureFormChrome();
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
    private function configureFields(): void
    {
        app(Customization::class)
            ->form('input')
            ->block('input.wrapper', 'focus:ring-primary-600 focus-within:focus:ring-primary-600 focus-within:ring-primary-600 dark:focus-within:ring-primary-400 flex rounded-md ring-1 focus-within:ring-2')
            ->block('input.base', 'dark:placeholder-dark-400 w-full rounded-md border-0 bg-transparent py-1.5 ring-0 placeholder:text-dark-500 focus:outline-hidden focus:ring-transparent sm:text-sm sm:leading-6')
            ->block('input.color.base', 'dark:ring-dark-400 dark:text-dark-300 text-dark-900 ring-dark-500')
            ->block('input.slot', 'dark:text-dark-400 flex select-none items-center whitespace-nowrap text-dark-500 sm:text-sm')
            ->block('error', $this->fieldError());

        app(Customization::class)
            ->form('textarea')
            ->block('error', $this->fieldError());
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
    private function configureFormChrome(): void
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
     * Invalid field: the package paints it red-600, which falls to 2.44:1 on a dark field.
     */
    private function fieldError(): string
    {
        return 'text-primary-700! dark:text-primary-300! ring-primary-600 placeholder:text-primary-700 focus-within:ring-primary-700 focus:ring-primary-700 focus-within:focus:ring-primary-700 dark:ring-primary-300 dark:focus-within:ring-primary-300';
    }
}
