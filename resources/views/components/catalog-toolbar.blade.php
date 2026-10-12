@props([
    'title',
    'subtitle' => null,
    'action',
    'filters' => [],
    'searchPlaceholder' => 'Cari...',
    'suggestType' => null,
])

@php
    $hasFilters = collect($filters)->except('q')->filter(fn ($value) => filled($value))->isNotEmpty();
    $query = $filters['q'] ?? '';
@endphp

<div {{ $attributes->merge(['class' => 'mx-auto max-w-6xl px-4']) }}>
    {{-- Judul halaman di paling atas --}}
    <h1 class="text-2xl font-bold text-slate-900">{{ $title }}</h1>
    @if ($subtitle)
        <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
    @endif

    {{-- Kolom pencarian + tombol filter --}}
    <form method="GET" action="{{ $action }}" class="mt-5" data-catalog-form>
        <div class="flex items-center gap-2">
            <div class="relative flex-1"
                 @if ($suggestType) data-suggest data-suggest-endpoint="{{ route('search.suggest', $suggestType) }}" @endif>
                <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">
                    <x-heroicon name="MagnifyingGlass" class="h-5 w-5" />
                </span>
                <input type="search" name="q" value="{{ $query }}" placeholder="{{ $searchPlaceholder }}"
                       autocomplete="off" data-suggest-input
                       class="w-full rounded-full border border-slate-200 bg-white py-3 pl-11 pr-4 text-sm text-slate-700 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100">

                {{-- Panel saran pencarian live --}}
                <div data-suggest-panel
                     class="absolute inset-x-0 top-full z-30 mt-2 hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg"></div>
            </div>

            <button type="button" data-filter-open
                    class="relative grid h-12 w-12 shrink-0 place-items-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-emerald-300 hover:text-emerald-700"
                    aria-label="{{ __('ui.catalog_filter_open') }}">
                <x-heroicon name="adjustments-horizontal" class="h-6 w-6" />
                @if ($hasFilters)
                    <span class="absolute right-2.5 top-2.5 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                @endif
            </button>
        </div>

        {{-- Modal filter (latar blur) --}}
        <div data-filter-modal class="fixed inset-0 z-50 hidden">
            <div data-filter-backdrop class="absolute inset-0 bg-slate-900/30 backdrop-blur-sm"></div>

            <div class="absolute inset-x-0 bottom-0 mx-auto max-h-[85vh] w-full overflow-y-auto rounded-t-3xl bg-white p-5 shadow-2xl sm:inset-0 sm:bottom-auto sm:top-1/2 sm:max-w-md sm:-translate-y-1/2 sm:rounded-3xl">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('ui.catalog_filter') }}</h2>
                    <button type="button" data-filter-close
                            class="grid h-9 w-9 place-items-center rounded-full text-slate-500 hover:bg-slate-100"
                            aria-label="{{ __('ui.catalog_filter_close') }}">
                        <x-heroicon name="x-mark" class="h-5 w-5" />
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    {{ $slot }}
                </div>

                <div class="mt-6 flex gap-3">
                    <a href="{{ $action }}"
                       class="flex-1 rounded-xl border border-slate-200 py-3 text-center text-sm font-medium text-slate-600 hover:bg-slate-50">
                        {{ __('ui.catalog_reset') }}
                    </a>
                    <button type="submit"
                            class="flex-1 rounded-xl bg-emerald-600 py-3 text-sm font-medium text-white hover:bg-emerald-700">
                        {{ __('ui.catalog_apply') }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@once
    <script>
        (function () {
            function initSuggest() {
                document.querySelectorAll('[data-suggest]').forEach(function (root) {
                    if (root.dataset.ready === '1') return;
                    root.dataset.ready = '1';

                    var input = root.querySelector('[data-suggest-input]');
                    var panel = root.querySelector('[data-suggest-panel]');
                    var endpoint = root.dataset.suggestEndpoint;
                    var timer = null;

                    function hide() { panel.classList.add('hidden'); panel.innerHTML = ''; }

                    function render(items) {
                        if (! items.length) { hide(); return; }

                        panel.innerHTML = items.map(function (item) {
                            var a = document.createElement('a');
                            a.href = item.url;
                            a.className = 'block px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50';
                            a.textContent = item.title;
                            return a.outerHTML;
                        }).join('');
                        panel.classList.remove('hidden');
                    }

                    input.addEventListener('input', function () {
                        var term = input.value.trim();
                        clearTimeout(timer);
                        if (term.length < 2) { hide(); return; }

                        timer = setTimeout(function () {
                            fetch(endpoint + '?q=' + encodeURIComponent(term), {
                                headers: { 'Accept': 'application/json' },
                            })
                                .then(function (res) { return res.ok ? res.json() : { items: [] }; })
                                .then(function (data) { render(data.items || []); })
                                .catch(hide);
                        }, 200);
                    });

                    document.addEventListener('click', function (event) {
                        if (! root.contains(event.target)) hide();
                    });
                });
            }

            function initFilter() {
                document.querySelectorAll('[data-filter-modal]').forEach(function (modal) {
                    if (modal.dataset.ready === '1') return;
                    modal.dataset.ready = '1';

                    var form = modal.closest('form');
                    var openBtn = form.querySelector('[data-filter-open]');

                    function open() { modal.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
                    function close() { modal.classList.add('hidden'); document.body.style.overflow = ''; }

                    openBtn.addEventListener('click', open);
                    modal.querySelectorAll('[data-filter-close], [data-filter-backdrop]').forEach(function (el) {
                        el.addEventListener('click', close);
                    });

                    document.addEventListener('keydown', function (event) {
                        if (event.key === 'Escape') close();
                    });
                });
            }

            function initInfinite() {
                document.querySelectorAll('[data-catalog-infinite]').forEach(function (root) {
                    if (root.dataset.ready === '1') return;
                    root.dataset.ready = '1';

                    var sentinel = root.querySelector('[data-catalog-sentinel]');
                    var loader = root.querySelector('[data-catalog-loader]');
                    var loading = false;

                    function loadMore() {
                        var next = root.dataset.next;
                        if (! next || loading) return;

                        loading = true;
                        if (loader) loader.classList.remove('hidden');

                        fetch(next, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                            .then(function (res) { return res.ok ? res.text() : ''; })
                            .then(function (html) {
                                if (! html) { root.dataset.next = ''; return; }

                                var doc = new DOMParser().parseFromString(html, 'text/html');
                                var grid = doc.querySelector('[data-catalog-grid]');
                                var fresh = doc.querySelector('[data-catalog-infinite]');

                                if (grid) {
                                    var target = root.querySelector('[data-catalog-grid]');
                                    Array.prototype.slice.call(grid.children).forEach(function (node) {
                                        target.appendChild(node);
                                    });
                                }

                                root.dataset.next = fresh ? (fresh.dataset.next || '') : '';
                            })
                            .catch(function () { root.dataset.next = ''; })
                            .finally(function () {
                                loading = false;
                                if (loader) loader.classList.add('hidden');
                            });
                    }

                    if ('IntersectionObserver' in window) {
                        var observer = new IntersectionObserver(function (entries) {
                            entries.forEach(function (entry) {
                                if (entry.isIntersecting) loadMore();
                            });
                        }, { rootMargin: '300px' });

                        observer.observe(sentinel);
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
                initSuggest();
                initFilter();
                initInfinite();
            });
        })();
    </script>
@endonce
