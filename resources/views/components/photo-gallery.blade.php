@props(['images' => [], 'alt' => ''])

@php
    $images = collect($images)->filter()->values()->all();
    $urls = array_map(fn (string $image): string => Storage::disk('public')->url($image), $images);
    $count = count($urls);
    $thumbs = array_slice($urls, 1, 3);
    $remaining = max(0, $count - 4);
    $thumbColumns = count($thumbs) === 1 ? 'grid-cols-1' : (count($thumbs) === 2 ? 'grid-cols-2' : 'grid-cols-3');
@endphp

@if ($count > 0)
    <div data-gallery-root data-gallery-alt="{{ $alt }}" class="relative">
        <div class="grid gap-2 {{ $count > 1 ? 'sm:grid-cols-4' : '' }}">
            <button type="button" data-gallery-open="0"
                    aria-label="{{ __('ui.gallery_open') }}: {{ $alt }}"
                    class="group relative aspect-[16/10] overflow-hidden rounded-3xl bg-slate-100 text-left shadow-sm {{ $count > 1 ? 'sm:col-span-3 sm:aspect-auto sm:min-h-[420px]' : 'sm:aspect-[16/8]' }}">
                <img src="{{ $urls[0] }}" alt="{{ $alt }}" fetchpriority="high"
                     class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.02]">

                <span class="absolute inset-0 bg-gradient-to-t from-slate-950/75 via-slate-950/10 to-transparent"></span>

                <span class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-3 p-4 text-white sm:p-5">
                    <span class="min-w-0">
                        <span class="block text-[10px] font-semibold uppercase tracking-[0.18em] text-white/70">{{ __('ui.gallery_label') }}</span>
                        <span class="mt-1 block text-sm font-bold">{{ __('ui.gallery_photos', ['count' => $count]) }}</span>
                    </span>
                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-white/15 px-3 py-1.5 text-[11px] font-semibold backdrop-blur transition group-hover:bg-white/25">
                        <x-heroicon name="photo" class="h-4 w-4" />
                        {{ __('ui.gallery_view') }}
                    </span>
                </span>
            </button>

            @if ($count > 1)
                <div class="grid {{ $thumbColumns }} gap-2 sm:flex sm:flex-col">
                    @foreach ($thumbs as $index => $url)
                        <button type="button" data-gallery-open="{{ $index + 1 }}"
                                aria-label="{{ __('ui.gallery_slide', ['number' => $index + 2]) }}"
                                class="group relative aspect-square overflow-hidden rounded-2xl bg-slate-100 shadow-sm sm:aspect-auto sm:min-h-0 sm:flex-1">
                            <img src="{{ $url }}" alt="" loading="lazy"
                                 class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            <span class="absolute inset-0 bg-slate-950/0 transition group-hover:bg-slate-950/10"></span>

                            @if ($loop->last && $remaining > 0)
                                <span class="absolute inset-0 grid place-items-center bg-slate-950/55 text-center text-white backdrop-blur-[1px]">
                                    <span class="block text-xl font-bold leading-none">+{{ $remaining }}</span>
                                    <span class="mt-1 block text-[10px] font-semibold uppercase tracking-wide text-white/80">{{ __('ui.gallery_more') }}</span>
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div data-gallery-modal class="fixed inset-0 z-[70] hidden" role="dialog" aria-modal="true" aria-label="{{ $alt }}">
            <div data-gallery-backdrop class="absolute inset-0 bg-slate-950/95 backdrop-blur-sm"></div>

            <div class="relative flex h-full flex-col">
                <div class="flex shrink-0 items-center justify-between gap-3 px-4 py-3 text-white sm:px-6">
                    <p data-gallery-counter class="text-sm font-semibold">1 / {{ $count }}</p>
                    <button type="button" data-gallery-close
                            class="grid h-10 w-10 place-items-center rounded-full bg-white/10 text-white transition hover:bg-white/20"
                            aria-label="{{ __('ui.gallery_close') }}">
                        <x-heroicon name="x-mark" class="h-5 w-5" />
                    </button>
                </div>

                <div class="relative flex min-h-0 flex-1 items-center justify-center px-3 pb-3 sm:px-6">
                    <button type="button" data-gallery-prev
                            class="absolute left-2 z-10 grid h-10 w-10 place-items-center rounded-full bg-white/10 text-white backdrop-blur transition hover:bg-white/20 sm:left-4 sm:h-11 sm:w-11"
                            aria-label="{{ __('ui.gallery_prev') }}">
                        <x-heroicon name="chevron-right" class="h-5 w-5 rotate-180" />
                    </button>

                    <img data-gallery-image src="{{ $urls[0] }}" alt="{{ $alt }}"
                         class="max-h-[70vh] max-w-full rounded-2xl object-contain shadow-2xl">

                    <button type="button" data-gallery-next
                            class="absolute right-2 z-10 grid h-10 w-10 place-items-center rounded-full bg-white/10 text-white backdrop-blur transition hover:bg-white/20 sm:right-4 sm:h-11 sm:w-11"
                            aria-label="{{ __('ui.gallery_next') }}">
                        <x-heroicon name="chevron-right" class="h-5 w-5" />
                    </button>
                </div>

                <div class="shrink-0 overflow-x-auto px-4 pb-4 sm:px-6">
                    <div class="mx-auto flex max-w-4xl gap-2">
                        @foreach ($urls as $index => $url)
                            <button type="button" data-gallery-thumb="{{ $index }}" data-src="{{ $url }}"
                                    aria-label="{{ __('ui.gallery_slide', ['number' => $index + 1]) }}"
                                    class="shrink-0 overflow-hidden rounded-lg transition {{ $index === 0 ? 'ring-2 ring-emerald-400' : 'opacity-60 hover:opacity-100' }}">
                                <img src="{{ $url }}" alt="" loading="lazy" class="h-14 w-20 object-cover">
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    @once
        <script>
            (function () {
                function init() {
                    document.querySelectorAll('[data-gallery-root]').forEach(function (root) {
                        if (root.dataset.ready === '1') return;
                        root.dataset.ready = '1';

                        var modal = root.querySelector('[data-gallery-modal]');
                        var image = root.querySelector('[data-gallery-image]');
                        var counter = root.querySelector('[data-gallery-counter]');
                        var closeButton = root.querySelector('[data-gallery-close]');
                        var thumbs = Array.prototype.slice.call(root.querySelectorAll('[data-gallery-thumb]'));
                        var openers = Array.prototype.slice.call(root.querySelectorAll('[data-gallery-open]'));
                        var sources = thumbs.map(function (thumb) { return thumb.dataset.src; });
                        var current = 0;
                        var lastFocused = null;

                        if (! modal || ! image || sources.length === 0) return;

                        function show(index) {
                            current = (index + sources.length) % sources.length;
                            image.src = sources[current];
                            image.alt = root.dataset.galleryAlt + ' — ' + (current + 1);
                            counter.textContent = (current + 1) + ' / ' + sources.length;

                            thumbs.forEach(function (thumb, thumbIndex) {
                                var active = thumbIndex === current;
                                thumb.classList.toggle('ring-2', active);
                                thumb.classList.toggle('ring-emerald-400', active);
                                thumb.classList.toggle('opacity-60', ! active);
                            });
                        }

                        function open(index) {
                            lastFocused = document.activeElement;
                            show(index);
                            modal.classList.remove('hidden');
                            document.body.style.overflow = 'hidden';
                            closeButton.focus();
                        }

                        function close() {
                            modal.classList.add('hidden');
                            document.body.style.overflow = '';
                            if (lastFocused) lastFocused.focus();
                        }

                        openers.forEach(function (button) {
                            button.addEventListener('click', function () {
                                open(parseInt(button.dataset.galleryOpen, 10) || 0);
                            });
                        });

                        root.querySelectorAll('[data-gallery-close], [data-gallery-backdrop]').forEach(function (element) {
                            element.addEventListener('click', close);
                        });

                        root.querySelector('[data-gallery-prev]').addEventListener('click', function () {
                            show(current - 1);
                        });

                        root.querySelector('[data-gallery-next]').addEventListener('click', function () {
                            show(current + 1);
                        });

                        thumbs.forEach(function (thumb) {
                            thumb.addEventListener('click', function () {
                                show(parseInt(thumb.dataset.galleryThumb, 10) || 0);
                            });
                        });

                        document.addEventListener('keydown', function (event) {
                            if (modal.classList.contains('hidden')) return;

                            if (event.key === 'Escape') close();
                            if (event.key === 'ArrowLeft') show(current - 1);
                            if (event.key === 'ArrowRight') show(current + 1);
                        });
                    });
                }

                document.addEventListener('DOMContentLoaded', init);
            })();
        </script>
    @endonce
@endif
