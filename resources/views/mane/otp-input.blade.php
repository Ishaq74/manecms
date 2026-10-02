{{--
    One-time code typed digit by digit, posted as a single hidden field. Pasting a
    whole code fills every box; Backspace on an empty box moves back.
--}}
@props([
    'name',
    'label',
    'length' => 6,
])

<div
    {{ $attributes->class('flex items-center justify-center gap-2') }}
    role="group"
    aria-label="{{ $label }}"
    data-otp
    x-data="{ digits: Array({{ (int) $length }}).fill('') }"
>
    <input type="hidden" name="{{ $name }}" x-bind:value="digits.join('')" />

    <template x-for="(digit, index) in digits" :key="index">
        <input
            type="text"
            inputmode="numeric"
            maxlength="1"
            x-bind:autocomplete="index === 0 ? 'one-time-code' : 'off'"
            x-bind:aria-label="@js(__('Digit')) + ' ' + (index + 1)"
            x-bind:value="digits[index]"
            x-on:input="digits[index] = $event.target.value.replace(/\D/g, '').slice(-1); $event.target.value = digits[index]; if (digits[index] && index < digits.length - 1) { $el.nextElementSibling?.focus() }"
            x-on:keydown.backspace="if (! digits[index] && index > 0) { $event.preventDefault(); $el.previousElementSibling?.focus() }"
            x-on:paste.prevent="($event.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, digits.length).split('').forEach((value, position) => digits[position] = value)"
            class="h-12 w-12 rounded-lg border border-line-strong bg-surface-raised text-center text-lg font-semibold text-fg focus:border-focus focus:ring-2 focus:ring-focus focus:outline-hidden"
        />
    </template>
</div>
