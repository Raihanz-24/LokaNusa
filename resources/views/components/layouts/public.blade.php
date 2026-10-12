<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#047857">
    {{-- Token CSRF untuk permintaan AJAX (mis. chat AI Trip Planner). --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <title>{{ $title ?? config('app.name', 'LokaNusa') }}</title>
    <meta name="description" content="{{ $metaDescription ?? 'Jelajahi destinasi wisata, UMKM, budaya, dan penginapan di seluruh Indonesia bersama LokaNusa.' }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex flex-col bg-slate-50 text-slate-800 antialiased {{ ($fullHeight ?? false) ? 'h-dvh overflow-hidden' : 'min-h-dvh' }}">
    @php
        $focusRegion = \App\Support\RegionResolver::focus();
        $locationLabel = $focusRegion?->fullName() ?? 'Probolinggo, Jawa Timur';
        $greetingName = auth()->check() ? Str::before(auth()->user()->name, ' ') : __('ui.guest_name');
    @endphp

    {{-- Header utama: selalu tampil (sticky) saat digulir --}}
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3">
            {{-- Logo brand (tampil di layar lebar) --}}
            <a href="{{ route('home') }}" class="hidden shrink-0 items-center gap-2 md:flex" aria-label="LokaNusa">
                <img src="{{ asset('images/brand/logo-mark-sm.png') }}" alt="LokaNusa"
                     class="h-10 w-10" width="96" height="96">
            </a>

            {{-- Foto/inisial profil --}}
            <a href="{{ auth()->check() ? route('profile.edit') : '#' }}"
               @guest data-auth-open @endguest
               class="shrink-0" aria-label="{{ __('ui.menu_profile') }}">
                @auth
                    <span class="grid h-11 w-11 place-items-center rounded-full bg-emerald-100 text-base font-semibold text-emerald-700 ring-2 ring-emerald-200">
                        {{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                    </span>
                @else
                    <span class="grid h-11 w-11 place-items-center rounded-full bg-slate-100 text-slate-500 ring-2 ring-slate-200">
                        <x-heroicon name="UserCircle" class="h-7 w-7" />
                    </span>
                @endauth
            </a>

            {{-- Sapaan + lokasi --}}
            <div class="min-w-0 flex-1">
                <p class="truncate text-base font-semibold leading-tight text-slate-800">{{ __('ui.greeting_hello') }}, {{ $greetingName }}</p>
                <p class="truncate text-xs text-slate-500">{{ $locationLabel }}</p>
            </div>

            {{-- Navigasi desktop --}}
            <nav class="hidden items-center gap-4 text-sm font-medium text-slate-600 md:flex">
                <a href="{{ route('home') }}" class="hover:text-emerald-700">{{ __('ui.nav_home') }}</a>
                <a href="{{ route('destinations.index') }}" class="hover:text-emerald-700">{{ __('ui.nav_destinations') }}</a>
                <a href="{{ route('umkm.index') }}" class="hover:text-emerald-700">{{ __('ui.nav_umkm') }}</a>
                <a href="{{ route('culture.index') }}" class="hover:text-emerald-700">{{ __('ui.nav_culture') }}</a>
                <a href="{{ route('events.index') }}" class="hover:text-emerald-700">{{ __('ui.nav_events') }}</a>
                <a href="{{ route('accommodations.index') }}" class="hover:text-emerald-700">{{ __('ui.nav_accommodations') }}</a>
                <a href="{{ route('trip-planner.index') }}" class="hover:text-emerald-700">{{ __('ui.nav_trip_planner') }}</a>
                <a href="{{ route('explore.map') }}" class="hover:text-emerald-700">{{ __('ui.nav_map') }}</a>
            </nav>

            {{-- Switch bahasa --}}
            <x-locale-switch class="hidden shrink-0 sm:inline-flex" variant="light" />

            {{-- Ikon notifikasi --}}
            <button type="button"
                    class="grid h-10 w-10 shrink-0 place-items-center rounded-full text-slate-600 transition hover:bg-slate-100"
                    aria-label="Notifikasi"
                    @guest data-auth-open @endguest>
                <x-heroicon name="Bell" class="h-6 w-6" />
            </button>

            {{-- Menu titik-tiga (dropdown) --}}
            <x-public-user-menu />
        </div>
    </header>

    <main class="flex-1 {{ ($fullHeight ?? false) ? 'flex min-h-0 flex-col overflow-hidden pb-[var(--bottom-nav-height)] md:pb-0' : 'pb-24 md:pb-0' }}">
        {{ $slot }}
    </main>

    @unless ($fullHeight ?? false)
    <footer class="mt-16 border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-col items-center gap-3 px-4 py-8 text-center text-sm text-slate-500 sm:flex-row sm:justify-between sm:text-left">
            <div class="flex flex-col items-center gap-2 sm:flex-row">
                <img src="{{ asset('images/brand/logo-mark-sm.png') }}" alt="LokaNusa"
                     class="h-9 w-9" width="96" height="96">
                <span class="font-semibold text-slate-700">LokaNusa</span>
            </div>
            <p>&copy; {{ date('Y') }} LokaNusa &mdash; {{ __('ui.brand_tagline') }}.</p>
        </div>
    </footer>
    @endunless

    {{-- Bottom navbar mobile tampil di semua halaman publik, termasuk halaman
         chat (agar navigasi tetap tersedia). --}}
    <x-public-bottom-nav />

    <x-pwa-install />

    @guest
        <x-auth-prompt :auto-open="session('auth_prompt', false)" />
    @endguest

    @stack('scripts')
</body>
</html>
