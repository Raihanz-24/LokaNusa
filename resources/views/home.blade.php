@php
    $focusRegion = \App\Support\RegionResolver::focus();
    $locationLabel = $focusRegion?->fullName() ?? 'Probolinggo, Jawa Timur';
    $locationShort = $focusRegion
        ? preg_replace('/^(Kabupaten|Kota)\s+/i', '', $focusRegion->name)
        : 'Probolinggo';
@endphp

<x-layouts.public :title="__('ui.title_home')">
    {{-- Hero utama: satu pesan kuat, CTA, pencarian, dan statistik nyata. --}}
    <section class="relative isolate overflow-hidden bg-slate-950 text-white" data-hero>
        {{-- Latar carousel: teks utama tetap, gambar berganti perlahan. --}}
        @if (! empty($banners))
            <div class="absolute inset-0" aria-hidden="true">
                <div data-hero-track class="flex h-full transition-transform duration-700 ease-out motion-reduce:transition-none">
                    @foreach ($banners as $banner)
                        <div class="relative h-full w-full shrink-0 overflow-hidden">
                            @if (! empty($banner['image_url']))
                                <img src="{{ $banner['image_url'] }}" alt=""
                                     class="h-full w-full object-cover"
                                     @if ($loop->first) fetchpriority="high" @elseif ($loop->iteration > 2) loading="lazy" @endif
                                     decoding="async">
                            @else
                                <div class="h-full w-full bg-gradient-to-br from-emerald-900 via-slate-900 to-slate-950"></div>
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-950/70 to-slate-900/15"></div>
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-transparent to-slate-950/20"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-950 via-slate-950 to-slate-900" aria-hidden="true"></div>
        @endif

        <div class="relative z-10 mx-auto flex min-h-[560px] max-w-6xl flex-col justify-center px-4 pb-6 pt-12 sm:min-h-[600px] sm:pt-16 lg:min-h-[640px] lg:pt-20">
            <div class="max-w-3xl">
                <p class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-white/85 backdrop-blur">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                    {{ $locationLabel }}
                </p>

                <h1 class="mt-5 max-w-3xl text-4xl font-bold leading-[1.08] tracking-tight text-white sm:text-5xl lg:text-6xl">
                    {{ __('ui.home_hero_title') }}
                </h1>

                <p class="mt-4 max-w-2xl text-sm leading-relaxed text-white/80 sm:text-base">
                    {{ __('ui.home_hero_subtitle') }}
                </p>

                <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('destinations.index') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-500 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-950/30 transition hover:bg-emerald-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/80">
                        {{ __('ui.home_hero_cta_explore') }}
                        <x-heroicon name="chevron-right" class="h-4 w-4" />
                    </a>
                    <a href="{{ route('trip-planner.index') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:border-white/30 hover:bg-white/15 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/80">
                        <x-heroicon name="sparkles" class="h-4 w-4 text-emerald-300" />
                        {{ __('ui.home_hero_cta_ai') }}
                    </a>
                </div>

                <form action="{{ route('destinations.index') }}" method="GET" class="mt-7 max-w-2xl">
                    <label for="hero-search" class="sr-only">{{ __('ui.home_search_placeholder') }}</label>
                    <div class="flex items-center gap-2 rounded-2xl border border-white/20 bg-white/95 p-1.5 shadow-2xl shadow-slate-950/30 backdrop-blur transition focus-within:border-emerald-300 focus-within:ring-2 focus-within:ring-emerald-300/40">
                        <x-heroicon name="MagnifyingGlass" class="ml-2 h-5 w-5 shrink-0 text-slate-400" />
                        <input id="hero-search" type="search" name="q" value="{{ request('q') }}"
                               placeholder="{{ __('ui.home_search_placeholder') }}"
                               class="min-w-0 flex-1 border-0 bg-transparent px-1 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:ring-0">
                        <button type="submit"
                                class="shrink-0 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                            {{ __('ui.home_hero_search_button') }}
                        </button>
                    </div>
                </form>

                <div class="mt-7 flex flex-wrap items-center gap-x-6 gap-y-3 text-xs text-white/70 sm:text-sm">
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-white">{{ number_format($totalDestinations, 0, ',', '.') }}</span>
                        <span>{{ __('ui.home_hero_stat_destinations') }}</span>
                    </div>
                    <span class="hidden h-8 w-px bg-white/20 sm:block" aria-hidden="true"></span>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-white">{{ number_format($totalUmkm, 0, ',', '.') }}</span>
                        <span>{{ __('ui.home_hero_stat_umkm') }}</span>
                    </div>
                    <span class="hidden h-8 w-px bg-white/20 sm:block" aria-hidden="true"></span>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-white">{{ number_format($totalAccommodations ?? 0, 0, ',', '.') }}</span>
                        <span>{{ __('ui.home_hero_stat_stays') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sorotan banner aktif + kontrol carousel. --}}
        @if (! empty($banners))
            <div class="relative z-10 mx-auto max-w-6xl px-4 pb-5">
                <div class="flex items-end justify-between gap-4 border-t border-white/15 pt-4">
                    <div class="min-w-0 flex-1">
                        @foreach ($banners as $i => $banner)
                            <a href="{{ $banner['link_url'] ?: route('destinations.index') }}"
                               data-hero-highlight="{{ $i }}"
                               class="{{ $i === 0 ? 'flex' : 'hidden' }} group min-w-0 items-center gap-2 text-xs text-white/70">
                                <span class="shrink-0 rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-emerald-200">
                                    {{ __('ui.home_hero_highlight') }}
                                </span>
                                <span class="truncate font-semibold text-white">{{ $banner['caption'] ?: $banner['title'] }}</span>
                                @if (! empty($banner['title']))
                                    <span class="hidden truncate text-white/55 sm:inline">{{ $banner['title'] }}</span>
                                @endif
                                <x-heroicon name="chevron-right" class="h-3.5 w-3.5 shrink-0 text-white/40 transition group-hover:translate-x-0.5 group-hover:text-white" />
                            </a>
                        @endforeach
                    </div>

                    @if (count($banners) > 1)
                        <div class="flex shrink-0 items-center gap-2">
                            <div class="flex items-center gap-1.5">
                                @foreach ($banners as $i => $banner)
                                    <button type="button" data-hero-dot="{{ $i }}"
                                            aria-label="{{ __('ui.home_hero_slide', ['number' => $i + 1]) }}"
                                            class="h-1.5 rounded-full transition-all duration-300 {{ $i === 0 ? 'w-6 bg-white' : 'w-1.5 bg-white/40 hover:bg-white/70' }}"></button>
                                @endforeach
                            </div>
                            <button type="button" data-hero-pause
                                    data-label-pause="{{ __('ui.home_hero_pause') }}"
                                    data-label-play="{{ __('ui.home_hero_play') }}"
                                    aria-label="{{ __('ui.home_hero_pause') }}"
                                    class="grid h-8 w-8 place-items-center rounded-full border border-white/20 bg-white/10 text-white backdrop-blur transition hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/80">
                                <span data-hero-pause-icon><x-heroicon name="pause" class="h-3.5 w-3.5" /></span>
                                <span data-hero-play-icon class="hidden"><x-heroicon name="play" class="h-3.5 w-3.5" /></span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </section>

    @if (! empty($banners) && count($banners) > 1)
        @once
            <script>
                (function () {
                    function init() {
                        document.querySelectorAll('[data-hero]').forEach(function (root) {
                            if (root.dataset.ready === '1') return;
                            root.dataset.ready = '1';

                            var track = root.querySelector('[data-hero-track]');
                            var dots = Array.prototype.slice.call(root.querySelectorAll('[data-hero-dot]'));
                            var highlights = Array.prototype.slice.call(root.querySelectorAll('[data-hero-highlight]'));
                            var pause = root.querySelector('[data-hero-pause]');
                            var pauseIcon = root.querySelector('[data-hero-pause-icon]');
                            var playIcon = root.querySelector('[data-hero-play-icon]');
                            var total = track ? track.children.length : 0;
                            if (! track || total <= 1) return;

                            var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                            var index = 0;
                            var timer = null;
                            var manualPaused = false;

                            function render() {
                                track.style.transform = 'translateX(-' + (index * 100) + '%)';

                                dots.forEach(function (dot, i) {
                                    var active = i === index;
                                    dot.classList.toggle('w-6', active);
                                    dot.classList.toggle('w-1.5', ! active);
                                    dot.classList.toggle('bg-white', active);
                                    dot.classList.toggle('bg-white/40', ! active);
                                    dot.setAttribute('aria-current', active ? 'true' : 'false');
                                });

                                highlights.forEach(function (item, i) {
                                    item.classList.toggle('flex', i === index);
                                    item.classList.toggle('hidden', i !== index);
                                });
                            }

                            function go(next) {
                                index = (next + total) % total;
                                render();
                            }

                            function stop() {
                                if (timer) {
                                    clearInterval(timer);
                                    timer = null;
                                }
                            }

                            function start() {
                                stop();
                                if (reduceMotion || manualPaused) return;
                                timer = setInterval(function () { go(index + 1); }, 6500);
                            }

                            function setPaused(paused) {
                                manualPaused = paused;

                                if (pause) {
                                    pause.setAttribute('aria-label', paused ? pause.dataset.labelPlay : pause.dataset.labelPause);
                                }
                                if (pauseIcon) pauseIcon.classList.toggle('hidden', paused);
                                if (playIcon) playIcon.classList.toggle('hidden', ! paused);

                                if (paused) {
                                    stop();
                                } else {
                                    start();
                                }
                            }

                            dots.forEach(function (dot, i) {
                                dot.addEventListener('click', function () {
                                    go(i);
                                    if (! manualPaused) start();
                                });
                            });

                            if (pause) {
                                pause.addEventListener('click', function () {
                                    setPaused(! manualPaused);
                                });
                            }

                            root.addEventListener('mouseenter', stop);
                            root.addEventListener('mouseleave', function () { if (! manualPaused) start(); });
                            root.addEventListener('focusin', stop);
                            root.addEventListener('focusout', function (event) {
                                if (! root.contains(event.relatedTarget) && ! manualPaused) start();
                            });
                            root.addEventListener('touchstart', stop, { passive: true });
                            root.addEventListener('touchend', function () { if (! manualPaused) start(); });

                            render();

                            if (reduceMotion) {
                                if (pause) pause.classList.add('hidden');
                            } else {
                                start();
                            }
                        });
                    }

                    document.addEventListener('DOMContentLoaded', init);
                })();
            </script>
        @endonce
    @endif

    {{-- Shortcut fitur --}}
    @if (! empty($shortcuts))
        <section class="mx-auto max-w-6xl px-4 py-6">
            <h2 class="mb-3 text-base font-bold text-slate-900">{{ __('ui.home_quick_access') }}</h2>

            <div class="grid grid-cols-4 gap-3 sm:grid-cols-5 lg:grid-cols-8">
                @foreach ($shortcuts as $shortcut)
                    <a href="{{ $shortcut['url'] }}"
                       class="flex flex-col items-center gap-1.5 rounded-2xl border border-slate-100 bg-white p-3 text-center shadow-sm transition hover:border-emerald-200 hover:shadow">
                        <span class="grid h-11 w-11 place-items-center rounded-full bg-emerald-50 text-emerald-600">
                            <x-heroicon :name="$shortcut['icon']" class="h-6 w-6" />
                        </span>
                        <span class="line-clamp-2 text-[11px] font-medium leading-tight text-slate-600">{{ $shortcut['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Rekomendasi untukmu --}}
    <section class="mx-auto max-w-6xl px-4 pb-12">
        <div class="mb-3 flex items-end justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">{{ __('ui.home_recommendations') }}</h2>
                <p class="text-xs text-slate-500">{{ __('ui.home_recommendations_near', ['region' => $focusRegion?->name ?? __('ui.home_you')]) }}</p>
            </div>
            <a href="{{ route('destinations.index') }}" class="text-sm font-medium text-emerald-700 hover:underline">{{ __('ui.catalog_see_all', ['label' => __('ui.destinations_label')]) }}</a>
        </div>

        @if ($recommendations->isEmpty())
            <p class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                {{ __('ui.home_empty', ['menu' => __('ui.home_empty_menu')]) }}
            </p>
        @else
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($recommendations as $card)
                    <x-recommendation-card :card="$card" />
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.public>
