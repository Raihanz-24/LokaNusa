<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#064e3b">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <title>{{ $title ?? 'LokaNusa' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative min-h-screen bg-slate-900 text-slate-100 antialiased">
    {{-- Latar belakang: Gunung Bromo & Semeru --}}
    <div class="fixed inset-0 bg-cover bg-center"
         style="background-image: url('{{ asset('images/auth/bromo-tengger-semeru.jpg') }}');"></div>
    <div class="fixed inset-0 bg-gradient-to-br from-slate-950/80 via-slate-900/55 to-emerald-950/75"></div>

    <div class="relative z-10 flex min-h-screen flex-col">
        <div class="mx-auto grid w-full max-w-6xl flex-1 items-center gap-8 px-4 py-8 sm:px-6 lg:grid-cols-2 lg:gap-12 lg:px-8 lg:py-10">
            {{-- Sisi kiri: sambutan (desktop) --}}
            <div class="hidden text-white lg:block">
                <h1 class="max-w-lg text-4xl font-bold leading-tight tracking-tight">
                    {{ __('ui.hero_heading') }}
                </h1>
                <p class="mt-5 max-w-md text-base leading-relaxed text-slate-200/90">
                    {{ __('ui.hero_subtitle') }}
                </p>

                <div class="mt-8 flex items-center gap-3 text-sm text-slate-300/80">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-white/10">🗺️</span>
                    <span>{{ __('ui.hero_note') }}</span>
                </div>
            </div>

            {{-- Sisi kanan: kartu formulir --}}
            <div class="mx-auto w-full max-w-md">
                {{-- Kartu gelap transparan agar foto gunung tetap terlihat --}}
                <div class="rounded-3xl border border-white/15 bg-slate-900/55 p-6 shadow-2xl backdrop-blur-md sm:p-8">
                    {{-- Header kartu: emblem (tanpa tulisan) + teks brand terpisah --}}
                    <div class="relative mb-6 flex flex-col items-center gap-3">
                        <div class="absolute right-0 top-0">
                            <x-locale-switch variant="dark" />
                        </div>

                        <a href="{{ route('home') }}" class="flex flex-col items-center gap-2">
                            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-white p-1.5 shadow-lg ring-1 ring-black/5">
                                <img src="{{ asset('images/brand/logo-mark-sm.png') }}"
                                     alt="Logo LokaNusa"
                                     class="h-full w-full object-contain"
                                     width="96" height="96">
                            </span>
                            <span class="text-xl font-semibold tracking-tight text-white">LokaNusa</span>
                        </a>
                    </div>

                    {{ $slot }}
                </div>

                <p class="mt-4 text-center text-xs text-slate-300/70">
                    {{ __('ui.photo_credit') }}
                </p>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
