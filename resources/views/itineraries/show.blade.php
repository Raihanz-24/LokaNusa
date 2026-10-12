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
        <p class="mt-2 text-sm text-slate-500">{{ $itinerary->items->count() }} butir &middot; {{ $itinerary->totalDays() }} hari</p>

        @if (session('status'))
            <p class="mt-4 rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-700">{{ session('status') }}</p>
        @endif

        @if ($itinerary->items->isEmpty())
            <p class="mt-8 rounded-2xl border border-dashed border-slate-300 p-8 text-center text-slate-500">
                Rencana masih kosong. Tambahkan destinasi, UMKM, penginapan, atau event lewat tombol
                <span class="font-medium">&ldquo;Tambah ke rencana&rdquo;</span> di halaman detailnya.
            </p>
        @else
            <div class="mt-8 space-y-8">
                @foreach ($days as $day => $items)
                    <section>
                        <h2 class="text-lg font-bold text-slate-900">Hari {{ $day }}</h2>

                        <ul class="mt-3 space-y-3">
                            @foreach ($items as $item)
                                @php
                                    $content = $item->item;
                                    $type = $content ? \App\Enums\EngagementType::fromModel($content) : null;
                                @endphp
                                @continue($content === null || $type === null)

                                <li class="rounded-2xl border border-slate-200 bg-white p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <span class="inline-block rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                                                {{ $type->label() }}
                                            </span>
                                            <a href="{{ route($type->detailRouteName(), $content->slug) }}"
                                               class="mt-1 block font-semibold text-slate-800 hover:text-emerald-700">
                                                {{ $content->name ?? $content->title ?? '—' }}
                                            </a>
                                            @if ($content->region)
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
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.public>
