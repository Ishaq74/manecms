<x-layouts::guest :title="__('Home')">
    <div class="py-12 sm:py-20">
        <div class="mx-auto max-w-2xl text-center">
            <h1 class="text-4xl font-semibold tracking-tight text-dark-900 sm:text-5xl dark:text-white">
                {{ config('app.name', 'Laravel') }}
            </h1>

            <p class="mt-4 text-lg text-dark-500 dark:text-dark-400">
                {{ __('One platform for your business.') }}
            </p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                @auth
                    <x-button :href="route('dashboard')" navigate :text="__('Go to dashboard')" />

                    <x-button flat :href="route('profile.edit')" navigate :text="__('Account settings')" />
                @else
                    <x-button :href="route('register')" navigate :text="__('Get started')" />

                    <x-button flat :href="route('login')" navigate :text="__('Log in')" />
                @endauth
            </div>
        </div>
    </div>
</x-layouts::guest>