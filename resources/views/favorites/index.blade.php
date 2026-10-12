<x-layouts.public title="Favorit Saya — LokaNusa" meta-description="Konten yang Anda simpan di LokaNusa.">
    <div class="mx-auto max-w-6xl px-4 py-10">
        <h1 class="text-2xl font-bold text-slate-900">Favorit Saya</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $favorites->total() }} konten tersimpan.</p>

        @if (session('status'))
            <p class="mt-4 rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-700">{{ session('status') }}</p>
        @endif

        @if ($favorites->isEmpty())
            <p class="mt-10 rounded-2xl border border-dashed border-slate-300 p-8 text-center text-slate-500">
                Belum ada favorit. Simpan destinasi, UMKM, penginapan, atau event dari halaman detailnya.
            </p>
        @else
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($favorites as $favorite)
                    @php
                        $item = $favorite->favoritable;
                        $type = $item ? \App\Enums\EngagementType::fromModel($item) : null;
                    @endphp
                    @continue($item === null || $type === null)

                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
                        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2 text-xs text-slate-500">
                            <span>{{ $type->label() }}</span>
                            <span>{{ $favorite->created_at->translatedFormat('d M Y') }}</span>
                        </div>
                        <div class="p-4">
                            <h2 class="font-semibold text-slate-800">{{ $item->name ?? $item->title ?? '—' }}</h2>
                            @if (! empty($item->region))
                                <p class="mt-1 text-sm text-slate-500">{{ $item->region->fullName() }}</p>
                            @endif

                            <a href="{{ route($type->detailRouteName(), $item->slug) }}"
                               class="mt-3 inline-block text-sm font-medium text-emerald-700 hover:underline">Lihat detail</a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $favorites->links() }}
            </div>
        @endif
    </div>
</x-layouts.public>
