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
        $locationShort = $focusRegion
            ? preg_replace('/^(Kabupaten|Kota)\s+/i', '', $focusRegion->name)
            : 'Probolinggo';
        $greetingName = auth()->check() ? Str::before(auth()->user()->name, ' ') : __('ui.guest_name');
        $exploreActive = request()->routeIs('destinations.*', 'umkm.*', 'accommodations.*', 'culture.*', 'events.*');
        $navBase = 'inline-flex items-center rounded-full px-3 py-2 text-sm font-medium transition';
        $navActive = 'bg-emerald-50 text-emerald-800';
        $navIdle = 'text-slate-600 hover:bg-slate-100 hover:text-slate-900';
    @endphp

    {{-- Header utama: selalu tampil (sticky) saat digulir --}}
    <header data-public-header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 shadow-none backdrop-blur-xl transition-shadow">
        <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-2.5">
            {{-- Logo brand (desktop) --}}
            <a href="{{ route('home') }}" class="hidden shrink-0 items-center gap-2 md:flex" aria-label="LokaNusa">
                <img src="{{ asset('images/brand/logo-mark-sm.png') }}" alt="LokaNusa"
                     class="h-9 w-9" width="96" height="96">
                <span class="hidden text-lg font-bold tracking-tight text-slate-900 lg:inline">LokaNusa</span>
            </a>

            {{-- Foto/inisial profil (mobile, tetap di kiri) --}}
            <a href="{{ auth()->check() ? route('profile.edit') : '#' }}"
               @guest data-auth-open @endguest
               class="shrink-0 md:hidden" aria-label="{{ __('ui.menu_profile') }}">
                @auth
                    <span class="grid h-10 w-10 place-items-center rounded-full bg-emerald-100 text-base font-semibold text-emerald-700 ring-2 ring-emerald-200">
                        {{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                    </span>
                @else
                    <span class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-slate-500 ring-2 ring-slate-200">
                        <x-heroicon name="UserCircle" class="h-6 w-6" />
                    </span>
                @endauth
            </a>

            {{-- Sapaan + lokasi (mobile) --}}
            <div class="min-w-0 flex-1 md:hidden">
                <p class="flex min-w-0 items-center gap-1.5 text-sm font-semibold leading-tight text-slate-800">
                    <img src="{{ asset('images/brand/logo-mark-sm.png') }}" alt=""
                         class="h-4 w-4 shrink-0" width="96" height="96">
                    <span class="truncate">{{ __('ui.greeting_hello') }}, {{ $greetingName }}</span>
                </p>
                <p class="mt-0.5 flex items-center gap-1 text-[11px] text-slate-500">
                    <x-heroicon name="map-pin" class="h-3 w-3 shrink-0 text-emerald-600" />
                    <span class="truncate">{{ $locationLabel }}</span>
                </p>
            </div>

            {{-- Navigasi desktop: 5 pintu masuk utama. --}}
            <nav class="hidden items-center gap-0.5 text-slate-600 md:flex" aria-label="Navigasi utama">
                <a href="{{ route('home') }}" @if (request()->routeIs('home')) aria-current="page" @endif
                   class="{{ $navBase }} {{ request()->routeIs('home') ? $navActive : $navIdle }}">
                    {{ __('ui.nav_home') }}
                </a>

                <details class="group relative" data-nav-dropdown>
                    <summary @if ($exploreActive) aria-current="page" @endif
                             class="{{ $navBase }} {{ $exploreActive ? $navActive : $navIdle }} cursor-pointer list-none [&::-webkit-details-marker]:hidden">
                        {{ __('ui.nav_explore') }}
                        <x-heroicon name="chevron-down" class="ml-1 h-3.5 w-3.5 transition group-open:rotate-180" />
                    </summary>

                    <div class="absolute left-0 top-full z-50 mt-2 w-64 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/10">
                        @php
                            $exploreLinks = [
                                ['route' => 'destinations.index', 'icon' => 'MapPin', 'label' => __('ui.nav_destinations')],
                                ['route' => 'umkm.index', 'icon' => 'BuildingStorefront', 'label' => __('ui.nav_umkm')],
                                ['route' => 'accommodations.index', 'icon' => 'Home', 'label' => __('ui.nav_accommodations')],
                                ['route' => 'culture.index', 'icon' => 'AcademicCap', 'label' => __('ui.nav_culture')],
                                ['route' => 'events.index', 'icon' => 'CalendarDays', 'label' => __('ui.nav_events')],
                            ];
                        @endphp

                        @foreach ($exploreLinks as $link)
                            <a href="{{ route($link['route']) }}"
                               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                                <span class="grid h-8 w-8 place-items-center rounded-full bg-emerald-50 text-emerald-700">
                                    <x-heroicon :name="$link['icon']" class="h-4 w-4" />
                                </span>
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </div>
                </details>

                <a href="{{ route('trip-planner.index') }}" @if (request()->routeIs('trip-planner.*')) aria-current="page" @endif
                   class="{{ $navBase }} {{ request()->routeIs('trip-planner.*') ? $navActive : $navIdle }}">
                    {{ __('ui.nav_trip_planner') }}
                </a>

                <a href="{{ route('explore.map') }}" @if (request()->routeIs('explore.*')) aria-current="page" @endif
                   class="{{ $navBase }} {{ request()->routeIs('explore.*') ? $navActive : $navIdle }}">
                    {{ __('ui.nav_map') }}
                </a>

                <a href="{{ auth()->check() ? route('itineraries.index') : route('login') }}" @if (request()->routeIs('itineraries.*')) aria-current="page" @endif
                   class="{{ $navBase }} {{ request()->routeIs('itineraries.*') ? $navActive : $navIdle }}">
                    {{ __('ui.nav_plans') }}
                </a>
            </nav>

            {{-- Chip lokasi desktop --}}
            <span class="ml-2 hidden items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-600 xl:inline-flex">
                <x-heroicon name="map-pin" class="h-3.5 w-3.5 text-emerald-600" />
                {{ $locationShort }}
            </span>

            {{-- Aksi kanan --}}
            <div class="ml-auto flex shrink-0 items-center gap-0.5">
                <x-locale-switch class="hidden lg:inline-flex" variant="light" />
                <x-notification-bell />
                <x-public-user-menu />
            </div>
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

    @once
        <script>
            (function () {
                var header = document.querySelector('[data-public-header]');

                if (header) {
                    var updateHeader = function () {
                        var scrolled = window.scrollY > 8;
                        header.classList.toggle('shadow-md', scrolled);
                        header.classList.toggle('shadow-slate-900/5', scrolled);
                    };

                    updateHeader();
                    window.addEventListener('scroll', updateHeader, { passive: true });
                }

                function closeNavDropdowns(except) {
                    document.querySelectorAll('details[data-nav-dropdown][open]').forEach(function (item) {
                        if (item !== except) item.open = false;
                    });
                }

                document.addEventListener('click', function (event) {
                    closeNavDropdowns(event.target.closest('details[data-nav-dropdown]'));
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') closeNavDropdowns(null);
                });
            })();
        </script>
    @endonce

    @stack('scripts')
</body>
</html>
