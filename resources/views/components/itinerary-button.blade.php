@props(['model', 'type'])

@php
    $itineraries = auth()->check()
        ? auth()->user()->itineraries()->withCount('items')->orderByDesc('updated_at')->get()
        : collect();

    // Rencana yang sudah memuat konten ini (agar tidak bisa ditambah ganda).
    $containingIds = $itineraries->isEmpty()
        ? []
        : $model->itineraryItems()
            ->whereIn('itinerary_id', $itineraries->pluck('id'))
            ->pluck('itinerary_id')
            ->all();
@endphp

<div data-itinerary-root>
    <button type="button" data-itinerary-open
            @guest data-auth-open @endguest
            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:border-emerald-300 hover:text-emerald-700">
        <x-heroicon name="Map" class="h-5 w-5" />
        Tambah ke rencana
    </button>

    @auth
        <div data-itinerary-modal class="fixed inset-0 z-50 hidden">
            <div data-itinerary-backdrop class="absolute inset-0 bg-slate-900/30 backdrop-blur-sm"></div>

            <div class="absolute inset-x-0 bottom-0 mx-auto max-h-[85vh] w-full overflow-y-auto rounded-t-3xl bg-white p-5 shadow-2xl sm:inset-0 sm:bottom-auto sm:top-1/2 sm:max-w-md sm:-translate-y-1/2 sm:rounded-3xl">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">Tambah ke rencana</h2>
                    <button type="button" data-itinerary-close
                            class="grid h-9 w-9 place-items-center rounded-full text-slate-500 hover:bg-slate-100"
                            aria-label="Tutup">
                        <x-heroicon name="x-mark" class="h-5 w-5" />
                    </button>
                </div>

                <p class="mt-1 text-sm text-slate-500">
                    Pilih rencana perjalanan tujuan untuk:
                    <span class="font-medium text-slate-700">{{ $model->name ?? $model->title }}</span>
                </p>

                @if (session('status'))
                    <p class="mt-3 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">{{ session('status') }}</p>
                @endif

                @if ($itineraries->isEmpty())
                    <p class="mt-4 rounded-xl border border-dashed border-slate-300 p-4 text-center text-sm text-slate-500">
                        Belum ada rencana. Buat rencana baru di bawah.
                    </p>
                @else
                    <ul class="mt-4 space-y-2">
                        @foreach ($itineraries as $itinerary)
                            @php $contains = in_array($itinerary->id, $containingIds, true); @endphp
                            <li>
                                <form method="POST" action="{{ route('itineraries.quick-add', ['type' => $type, 'id' => $model->getKey()]) }}">
                                    @csrf
                                    <input type="hidden" name="itinerary_id" value="{{ $itinerary->id }}">
                                    <button type="submit" @disabled($contains)
                                            class="flex w-full items-center justify-between gap-3 rounded-xl border px-4 py-3 text-left text-sm transition
                                                   {{ $contains
                                                        ? 'cursor-not-allowed border-slate-200 bg-slate-50 text-slate-400'
                                                        : 'border-slate-200 bg-white text-slate-700 hover:border-emerald-300 hover:bg-emerald-50' }}">
                                        <span class="min-w-0">
                                            <span class="block truncate font-medium">{{ $itinerary->title }}</span>
                                            <span class="block text-xs text-slate-400">{{ $itinerary->items_count }} butir</span>
                                        </span>
                                        @if ($contains)
                                            <span class="shrink-0 rounded-full bg-slate-200 px-2.5 py-0.5 text-xs font-medium text-slate-500">
                                                Sudah ada
                                            </span>
                                        @else
                                            <span class="shrink-0 text-xs font-semibold text-emerald-700">Tambah</span>
                                        @endif
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <form method="POST" action="{{ route('itineraries.quick-add', ['type' => $type, 'id' => $model->getKey()]) }}"
                      class="mt-5 border-t border-slate-100 pt-4">
                    @csrf
                    <label class="text-sm font-medium text-slate-700" for="itinerary-new-title-{{ $model->getKey() }}">
                        Buat rencana baru
                    </label>
                    <div class="mt-1 flex gap-2">
                        <input id="itinerary-new-title-{{ $model->getKey() }}" type="text" name="title" maxlength="120"
                               placeholder="Mis. Liburan Bromo 3 Hari"
                               class="w-full rounded-lg border-slate-300 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <button type="submit"
                                class="shrink-0 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-700">
                            Buat
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endauth
</div>

@once
    <script>
        (function () {
            function init() {
                document.querySelectorAll('[data-itinerary-modal]').forEach(function (modal) {
                    if (modal.dataset.ready === '1') return;
                    modal.dataset.ready = '1';

                    var root = modal.closest('[data-itinerary-root]');
                    var openBtn = root.querySelector('[data-itinerary-open]');

                    function open() { modal.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
                    function close() { modal.classList.add('hidden'); document.body.style.overflow = ''; }

                    openBtn.addEventListener('click', function (event) {
                        if (openBtn.hasAttribute('data-auth-open')) return;
                        event.preventDefault();
                        open();
                    });

                    modal.querySelectorAll('[data-itinerary-close], [data-itinerary-backdrop]').forEach(function (el) {
                        el.addEventListener('click', close);
                    });

                    document.addEventListener('keydown', function (event) {
                        if (event.key === 'Escape') close();
                    });
                });
            }

            document.addEventListener('DOMContentLoaded', init);
        })();
    </script>
@endonce
