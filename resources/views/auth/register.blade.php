<x-guest-layout :title="__('ui.create_account').' — LokaNusa'">
    <h1 class="text-center text-2xl font-bold text-white">{{ __('ui.register_title') }}</h1>
    <p class="mt-1 text-center text-sm text-slate-300">{{ __('ui.register_subtitle') }}</p>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4" novalidate>
        @csrf
        <input type="hidden" name="redirect" value="{{ old('redirect', request('redirect')) }}">

        <x-auth.field
            name="name"
            :label="__('ui.name')"
            :placeholder="__('ui.name_placeholder')"
            autocomplete="name"
            :autofocus="true" />

        <x-auth.field
            name="email"
            :label="__('ui.email')"
            type="email"
            autocomplete="username"
            placeholder="nama@email.com" />

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
            {{ __('ui.create_account') }}
        </x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-300">
        {{ __('ui.have_account') }}
        <a href="{{ route('login', ['redirect' => request('redirect')]) }}"
           class="font-semibold text-emerald-300 hover:text-emerald-200 hover:underline">{{ __('ui.sign_in_link') }}</a>
    </p>
</x-guest-layout>
