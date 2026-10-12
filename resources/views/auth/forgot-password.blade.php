<x-guest-layout :title="__('ui.forgot_title').' — LokaNusa'">
    <h1 class="text-center text-2xl font-bold text-white">{{ __('ui.forgot_title') }}</h1>
    <p class="mt-1 text-center text-sm text-slate-300">{{ __('ui.forgot_subtitle') }}</p>

    <x-auth.session-status class="mt-5" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4" novalidate>
        @csrf

        <x-auth.field
            name="email"
            :label="__('ui.email')"
            type="email"
            autocomplete="username"
            placeholder="nama@email.com"
            :autofocus="true" />

        <x-primary-button class="w-full">
            {{ __('ui.send_reset_link') }}
        </x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-300">
        <a href="{{ route('login') }}" class="font-medium text-emerald-300 hover:text-emerald-200 hover:underline">
            &larr; {{ __('ui.back_to_login') }}
        </a>
    </p>
</x-guest-layout>
