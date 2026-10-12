<x-guest-layout :title="__('ui.confirm_title').' — LokaNusa'">
    <h1 class="text-center text-2xl font-bold text-white">{{ __('ui.confirm_title') }}</h1>
    <p class="mt-1 text-center text-sm text-slate-300">{{ __('ui.confirm_subtitle') }}</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-4" novalidate>
        @csrf

        <x-auth.field
            name="password"
            :label="__('ui.password')"
            type="password"
            autocomplete="current-password"
            placeholder="••••••••"
            :autofocus="true" />

        <x-primary-button class="w-full">
            {{ __('ui.confirm_button') }}
        </x-primary-button>
    </form>
</x-guest-layout>
