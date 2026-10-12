<x-layouts.public title="Rencana Perjalanan — LokaNusa" meta-description="Susun dan simpan rencana perjalanan wisata Anda di LokaNusa.">
    <div class="mx-auto max-w-6xl px-4 py-10">
        <h1 class="text-2xl font-bold text-slate-900">Rencana Perjalanan</h1>
        <p class="mt-1 text-sm text-slate-500">Susun destinasi, kuliner, penginapan, dan event ke dalam rencana harian Anda.</p>

        @if (session('status'))
            <p class="mt-4 rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-700">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('itineraries.store') }}"
              class="mt-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-[1fr_auto]">
            @csrf
            <div>
                <label class="text-sm font-medium text-slate-700" for="itinerary-title">Nama rencana baru</label>
                <input id="itinerary-title" type="text" name="title" value="{{ old('title') }}" maxlength="120" required
                       placeholder="Mis. Liburan Bromo 3 Hari"
                       class="mt-1 w-full rounded-lg border-slate-300 py-2.5 focus:border-emerald-500 focus:ring-emerald-500">
                @error('title')
                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex items-end">
                <button type="submit"
                        class="w-full rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 sm:w-auto">
                    Buat Rencana
                </button>
            </div>
        </form>

        @if ($itineraries->isEmpty())
            <p class="mt-10 rounded-2xl border border-dashed border-slate-300 p-8 text-center text-slate-500">
                Belum ada rencana. Buat rencana, lalu tambahkan destinasi dari halaman detailnya.
            </p>
        @else
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($itineraries as $itinerary)
                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
                        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2 text-xs text-slate-500">
                            <span>{{ $itinerary->items_count }} butir</span>
                            <span>{{ $itinerary->created_at->translatedFormat('d M Y') }}</span>
                        </div>
                        <div class="p-4">
                            <h2 class="font-semibold text-slate-800">{{ $itinerary->title }}</h2>
                            @if ($itinerary->description)
                                <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $itinerary->description }}</p>
                            @endif

                            <div class="mt-3 flex items-center gap-3">
                                <a href="{{ route('itineraries.show', $itinerary) }}"
                                   class="text-sm font-medium text-emerald-700 hover:underline">Buka rencana</a>
                                <form method="POST" action="{{ route('itineraries.destroy', $itinerary) }}"
                                      onsubmit="return confirm('Hapus rencana ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-rose-600 hover:underline">Hapus</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $itineraries->links() }}
            </div>
        @endif
    </div>
</x-layouts.public>
