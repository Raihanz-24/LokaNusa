@php
    $focusRegion = \App\Support\RegionResolver::focus();
@endphp

<x-layouts.public :title="__('ui.title_home')">
    {{-- Di desktop, sapaan ringkas --}}
    <section class="mx-auto hidden max-w-6xl px-4 pt-6 md:block">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('ui.home_heading') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('ui.home_subtitle', ['count' => number_format($totalDestinations, 0, ',', '.')]) }}</p>
    </section>

    {{-- Carousel gambar destinasi ikonik --}}
    @if (! empty($banners))
        <section class="relative px-4 pt-4">
            <div class="mx-auto max-w-6xl">
                <div data-carousel class="relative overflow-hidden rounded-2xl">
                    <div data-carousel-track class="flex transition-transform duration-500 ease-out">
                        @foreach ($banners as $banner)
                            @php $link = $banner['link_url'] ?? null; @endphp
                            <div class="relative w-full shrink-0">
                                @if ($link)
                                    <a href="{{ $link }}" class="block">
                                @endif
                                <div class="relative aspect-[16/9] w-full bg-slate-200">
                                    @if (! empty($banner['image_url']))
                                        <img src="{{ $banner['image_url'] }}" alt="{{ $banner['title'] }}"
                                             class="h-full w-full object-cover" loading="lazy">
                                    @endif
                                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/70 via-slate-900/10 to-transparent"></div>

                                    <div class="absolute inset-x-0 bottom-0 p-4 text-white">
                                        @if (! empty($banner['caption']))
                                            <span class="inline-block rounded-full bg-black/40 px-3 py-0.5 text-xs font-medium backdrop-blur">
                                                {{ $banner['caption'] }}
                                            </span>
                                        @endif
                                        @if (! empty($banner['title']))
                                            <h2 class="mt-2 text-lg font-bold drop-shadow sm:text-xl">{{ $banner['title'] }}</h2>
                                        @endif
                                        @if (! empty($banner['subtitle']))
                                            <p class="mt-0.5 line-clamp-2 text-sm text-white/90">{{ $banner['subtitle'] }}</p>
                                        @endif
                                    </div>
                                </div>
                                @if ($link)
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if (count($banners) > 1)
                        <div data-carousel-dots class="absolute bottom-3 right-4 flex gap-1.5">
                            @foreach ($banners as $i => $banner)
                                <button type="button" data-carousel-dot="{{ $i }}"
                                        class="h-1.5 rounded-full bg-white/60 transition-all"
                                        style="width: {{ $i === 0 ? '1.5rem' : '0.375rem' }}"
                                        aria-label="Gambar {{ $i + 1 }}"></button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>

        @once
            <script>
                (function () {
                    function init() {
                        document.querySelectorAll('[data-carousel]').forEach(function (root) {
                            if (root.dataset.ready === '1') return;
                            root.dataset.ready = '1';

                            var track = root.querySelector('[data-carousel-track]');
                            var dots = Array.prototype.slice.call(root.querySelectorAll('[data-carousel-dot]'));
                            var total = track ? track.children.length : 0;
                            if (! track || total <= 1) return;

                            var index = 0;
                            var timer = null;

                            function render() {
                                track.style.transform = 'translateX(-' + (index * 100) + '%)';
                                dots.forEach(function (dot, i) {
                                    dot.style.width = i === index ? '1.5rem' : '0.375rem';
                                    dot.style.backgroundColor = i === index ? '#ffffff' : 'rgba(255,255,255,0.6)';
                                });
                            }

                            function go(next) {
                                index = (next + total) % total;
                                render();
                            }

                            function start() {
                                stop();
                                timer = setInterval(function () { go(index + 1); }, 3000);
                            }

                            function stop() {
                                if (timer) { clearInterval(timer); timer = null; }
                            }

                            dots.forEach(function (dot, i) {
                                dot.addEventListener('click', function () { go(i); start(); });
                            });

                            root.addEventListener('mouseenter', stop);
                            root.addEventListener('mouseleave', start);
                            root.addEventListener('touchstart', stop, { passive: true });
                            root.addEventListener('touchend', start);

                            render();
                            start();
                        });
                    }

                    document.addEventListener('DOMContentLoaded', init);
                })();
            </script>
        @endonce
    @endif

    {{-- Kolom pencarian (di bawah gambar) --}}
    <section class="mx-auto max-w-6xl px-4 pt-5">
        <form action="{{ route('destinations.index') }}" method="GET">
            <div class="flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-3 shadow-sm transition focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-100">
                <x-heroicon name="MagnifyingGlass" class="h-5 w-5 shrink-0 text-slate-400" />
                <input type="search" name="q" value="{{ request('q') }}"
                       placeholder="{{ __('ui.home_search_placeholder') }}"
                       class="w-full border-0 bg-transparent p-0 font-poppins text-sm text-slate-700 placeholder:text-slate-400 focus:ring-0">
            </div>
        </form>
    </section>

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
