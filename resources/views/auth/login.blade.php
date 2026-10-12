<x-guest-layout :title="__('ui.sign_in').' — LokaNusa'">
    <p class="text-center text-sm text-slate-300">{{ __('ui.welcome_subtitle') }}</p>

    <!-- Status sesi -->
    <x-auth.session-status class="mt-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4" novalidate>
        @csrf
        <input type="hidden" name="redirect" value="{{ old('redirect', request('redirect')) }}">

        <x-auth.field
            name="email"
            :label="__('ui.email')"
            type="email"
            autocomplete="username"
            placeholder="nama@email.com"
            :autofocus="true" />

        <x-auth.field
            name="password"
            :label="__('ui.password')"
            type="password"
            autocomplete="current-password"
            placeholder="••••••••" />

        <!-- Ingat saya -->
        <div class="flex items-center justify-between pt-1">
            <label for="remember_me" class="inline-flex cursor-pointer items-center gap-2.5 select-none">
                <input id="remember_me" type="checkbox" value="1" name="remember" @checked(old('remember'))
                       class="peer sr-only">
                <span class="grid h-5 w-5 shrink-0 place-items-center rounded-md border border-white/25 bg-white/5 text-transparent transition
                             peer-checked:border-emerald-400 peer-checked:bg-emerald-500 peer-checked:text-white
                             peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-400/50">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5">
                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                    </svg>
                </span>
                <span class="text-sm text-slate-300">{{ __('ui.remember_me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-emerald-300 hover:text-emerald-200 hover:underline"
                   href="{{ route('password.request') }}">
                    {{ __('ui.forgot_password') }}
                </a>
            @endif
        </div>

        <x-primary-button class="w-full">
            {{ __('ui.sign_in') }}
        </x-primary-button>
    </form>

    <!-- Unduh aplikasi (PWA) -->
    <x-auth.app-download class="mt-4" />

    <p class="mt-6 text-center text-sm text-slate-300">
        {{ __('ui.no_account') }}
        <a href="{{ route('register', ['redirect' => request('redirect')]) }}"
           class="font-semibold text-emerald-300 hover:text-emerald-200 hover:underline">{{ __('ui.register_free') }}</a>
    </p>
</x-guest-layout>
