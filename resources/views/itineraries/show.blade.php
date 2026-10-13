<x-layouts.public :title="$itinerary->title.' — Rencana LokaNusa'" meta-description="Rencana perjalanan wisata Anda.">
    <div class="mx-auto max-w-4xl px-4 py-10">
        <nav class="mb-4 text-sm text-slate-500">
            <a href="{{ route('itineraries.index') }}" class="hover:text-emerald-700">Rencana Perjalanan</a>
            <span class="mx-1">/</span>
            <span class="text-slate-700">{{ $itinerary->title }}</span>
        </nav>

        <h1 class="text-2xl font-bold text-slate-900">{{ $itinerary->title }}</h1>
        @if ($itinerary->description)
            <p class="mt-1 text-sm text-slate-500">{{ $itinerary->description }}</p>
        @endif
        <p class="mt-2 text-sm text-slate-500">{{ $itinerary->items->count() }} butir &middot; {{ $totalDays }} hari</p>

        @if (session('status'))
            <p class="mt-4 rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-700">{{ session('status') }}</p>
        @endif

        @if ($itinerary->hasCostEstimate())
            @php
                $costByDay = $itinerary->costByDay();
                $totalCost = $itinerary->totalCost();
            @endphp
            <div class="mt-4 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <span class="text-sm font-medium text-emerald-800">{{ __('ui.cost_total') }}</span>
                    <span class="text-xl font-bold text-emerald-800">{{ \App\Support\Rupiah::format($totalCost) }}</span>
                </div>
                <p class="mt-1 text-xs text-emerald-700">{{ __('ui.cost_estimate_note') }}</p>
            </div>
        @endif

        @if ($itinerary->items->isEmpty())
            <p class="mt-8 rounded-2xl border border-dashed border-slate-300 p-8 text-center text-slate-500">
                Rencana masih kosong. Tambahkan destinasi, UMKM, penginapan, atau event lewat tombol
                <span class="font-medium">&ldquo;Tambah ke rencana&rdquo;</span>, atau tulis kegiatan sendiri di bawah.
            </p>
        @endif

        <div class="mt-8 space-y-8">
            @for ($day = 1; $day <= max($totalDays, 1); $day++)
                @php $items = $days[$day] ?? collect(); @endphp
                <section>
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-bold text-slate-900">Hari {{ $day }}</h2>
                        <span class="h-px flex-1 bg-slate-200"></span>
                        @if (! empty($costByDay[$day]))
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                {{ __('ui.cost_day_total') }}: {{ \App\Support\Rupiah::format($costByDay[$day]) }}
                            </span>
                        @endif
                    </div>

                    @if ($items->isEmpty())
                        <p class="mt-3 rounded-xl border border-dashed border-slate-200 px-4 py-3 text-sm text-slate-400">
                            Belum ada kegiatan untuk hari ini.
                        </p>
                    @else
                        <ul class="mt-3 space-y-3">
                            @foreach ($items as $item)
                                @php
                                    $content = $item->item;
                                    $type = $content ? \App\Enums\EngagementType::fromModel($content) : null;
                                    $cost = $item->effectiveCost();
                                @endphp

                                <li class="rounded-2xl border border-slate-200 bg-white p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                @if ($item->timeLabel())
                                                    <span class="rounded-md bg-slate-900 px-2 py-0.5 text-xs font-semibold text-white">
                                                        {{ $item->timeLabel() }}
                                                    </span>
                                                @endif

                                                @if ($type)
                                                    <span class="inline-block rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                                                        {{ $type->label() }}
                                                    </span>
                                                @else
                                                    <span class="inline-block rounded-full bg-sky-50 px-2.5 py-0.5 text-xs font-medium text-sky-700">
                                                        Kegiatan
                                                    </span>
                                                @endif

                                                @if ($cost !== null)
                                                    <span class="inline-block rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">
                                                        {{ \App\Support\Rupiah::label($cost) }}{{ $item->effectiveCostNote() }}
                                                    </span>
                                                @endif
                                            </div>

                                            @if ($content && $type)
                                                <a href="{{ route($type->detailRouteName(), $content->slug) }}"
                                                   class="mt-1 block font-semibold text-slate-800 hover:text-emerald-700">
                                                    {{ $content->name ?? $content->title ?? '—' }}
                                                </a>
                                            @else
                                                <span class="mt-1 block font-semibold text-slate-800">
                                                    {{ $item->title ?? '—' }}
                                                </span>
                                            @endif

                                            @if ($item->location)
                                                <p class="text-sm text-slate-500">{{ $item->location }}</p>
                                            @elseif ($content && $content->region)
                                                <p class="text-sm text-slate-500">{{ $content->region->fullName() }}</p>
                                            @endif

                                            @if ($item->note)
                                                <p class="mt-1 text-sm text-slate-600">{{ $item->note }}</p>
                                            @endif
                                        </div>

                                        <form method="POST" action="{{ route('itineraries.items.destroy', $item) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-medium text-rose-600 hover:underline">Hapus</button>
                                        </form>
                                    </div>

                                    <form method="POST" action="{{ route('itineraries.items.update', $item) }}"
                                          class="mt-3 flex flex-wrap items-end gap-2 border-t border-slate-100 pt-3">
                                        @csrf
                                        @method('PATCH')
                                        <label class="text-xs font-medium text-slate-600">
                                            Hari
                                            <input type="number" name="day" value="{{ $item->day }}" min="1" max="60"
                                                   class="mt-0.5 block w-20 rounded-lg border-slate-300 py-1.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                        </label>
                                        <label class="text-xs font-medium text-slate-600">
                                            Mulai
                                            <input type="time" name="start_time" value="{{ \Illuminate\Support\Str::substr((string) $item->start_time, 0, 5) }}"
                                                   class="mt-0.5 block rounded-lg border-slate-300 py-1.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                        </label>
                                        <label class="text-xs font-medium text-slate-600">
                                            Selesai
                                            <input type="time" name="end_time" value="{{ \Illuminate\Support\Str::substr((string) $item->end_time, 0, 5) }}"
                                                   class="mt-0.5 block rounded-lg border-slate-300 py-1.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                        </label>
                                        @if ($item->isActivity())
                                            <label class="flex-1 text-xs font-medium text-slate-600">
                                                Kegiatan
                                                <input type="text" name="title" value="{{ $item->title }}" maxlength="180"
                                                       class="mt-0.5 block w-full rounded-lg border-slate-300 py-1.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                            </label>
                                        @endif
                                        <label class="text-xs font-medium text-slate-600">
                                            Biaya (Rp)
                                            <input type="number" name="cost" value="{{ $item->cost }}" min="0" step="1000" placeholder="0"
                                                   class="mt-0.5 block w-28 rounded-lg border-slate-300 py-1.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                        </label>
                                        <label class="flex-1 text-xs font-medium text-slate-600">
                                            Catatan
                                            <input type="text" name="note" value="{{ $item->note }}" maxlength="500"
                                                   placeholder="Mis. berangkat pukul 05.00"
                                                   class="mt-0.5 block w-full rounded-lg border-slate-300 py-1.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                        </label>
                                        <button type="submit"
                                                class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
                                            Simpan
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endfor
        </div>

        {{-- Tambah kegiatan bebas --}}
        <div class="mt-10 rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-base font-semibold text-slate-800">Tambah kegiatan</h2>
            <p class="mt-1 text-sm text-slate-500">Tulis kegiatan sendiri (mis. istirahat, perjalanan, kuliner yang belum terdaftar).</p>

            <form method="POST" action="{{ route('itineraries.activities.store', $itinerary) }}"
                  class="mt-4 grid gap-3 sm:grid-cols-2">
                @csrf
                <label class="text-sm font-medium text-slate-700 sm:col-span-2">
                    Nama kegiatan
                    <input type="text" name="title" value="{{ old('title') }}" maxlength="180" required
                           placeholder="Mis. Sarapan di warung lokal"
                           class="mt-1 block w-full rounded-lg border-slate-300 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Hari ke-
                    <input type="number" name="day" value="{{ old('day', 1) }}" min="1" max="60" required
                           class="mt-1 block w-full rounded-lg border-slate-300 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Lokasi (opsional)
                    <input type="text" name="location" value="{{ old('location') }}" maxlength="180"
                           class="mt-1 block w-full rounded-lg border-slate-300 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Jam mulai (opsional)
                    <input type="time" name="start_time" value="{{ old('start_time') }}"
                           class="mt-1 block w-full rounded-lg border-slate-300 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Jam selesai (opsional)
                    <input type="time" name="end_time" value="{{ old('end_time') }}"
                           class="mt-1 block w-full rounded-lg border-slate-300 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Biaya (Rp, opsional)
                    <input type="number" name="cost" value="{{ old('cost') }}" min="0" step="1000" placeholder="0"
                           class="mt-1 block w-full rounded-lg border-slate-300 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Satuan biaya (opsional)
                    <input type="text" name="cost_note" value="{{ old('cost_note') }}" maxlength="60"
                           placeholder="Mis. per orang / per malam"
                           class="mt-1 block w-full rounded-lg border-slate-300 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </label>
                <label class="text-sm font-medium text-slate-700 sm:col-span-2">
                    Catatan (opsional)
                    <input type="text" name="note" value="{{ old('note') }}" maxlength="500"
                           class="mt-1 block w-full rounded-lg border-slate-300 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </label>
                <div class="sm:col-span-2">
                    <button type="submit"
                            class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-700">
                        Tambah kegiatan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.public>
