<x-guest-layout :title="__('ui.reset_title').' — LokaNusa'">
    <h1 class="text-center text-2xl font-bold text-white">{{ __('ui.reset_title') }}</h1>
    <p class="mt-1 text-center text-sm text-slate-300">{{ __('ui.reset_subtitle') }}</p>

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4" novalidate>
        @csrf

        <!-- Token Reset -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-auth.field
            name="email"
            :label="__('ui.email')"
            type="email"
            :value="$request->email"
            autocomplete="username"
            :autofocus="true" />

        <x-auth.field
            name="password"
            :label="__('ui.password')"
            type="password"
            autocomplete="new-password"
            placeholder="••••••••"
            :hint="__('ui.password_hint')" />

        <x-auth.field
            name="password_confirmation"
            :label="__('ui.confirm_password')"
            type="password"
            autocomplete="new-password"
            placeholder="••••••••" />

        <x-primary-button class="w-full">
            {{ __('ui.save_new_password') }}
        </x-primary-button>
    </form>
</x-guest-layout>
