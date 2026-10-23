<x-filament-panels::page>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($this->periodOptions() as $value => $label)
                <button
                    type="button"
                    wire:click="setPeriod({{ $value }})"
                    @class([
                        'rounded-full border px-4 py-1.5 text-sm font-medium transition',
                        'border-primary-600 bg-primary-600 text-white' => $days === $value,
                        'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' => $days !== $value,
                    ])
                >
                    {{ $label }} hari
                </button>
            @endforeach
        </div>

        <x-filament::button wire:click="exportCsv" icon="heroicon-o-arrow-down-tray" color="success">
            Unduh Laporan CSV
        </x-filament::button>
    </div>

    {{-- KPI --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $cards = [
                ['label' => 'Pemindaian QR', 'value' => number_format($summary['qr_scans'] ?? 0), 'hint' => 'Total: '.number_format($summary['qr_scans_total'] ?? 0)],
                ['label' => 'Ulasan Terbit', 'value' => number_format($summary['reviews'] ?? 0), 'hint' => ($summary['rating_average'] ?? null) ? 'Rata-rata ★ '.$summary['rating_average'] : 'Belum ada rating'],
                ['label' => 'Favorit Baru', 'value' => number_format($summary['favorites'] ?? 0), 'hint' => 'Pengguna baru: '.number_format($summary['users_new'] ?? 0)],
                ['label' => 'Konten Terbit', 'value' => number_format($summary['content_published'] ?? 0), 'hint' => 'Baru: '.number_format($summary['content_new'] ?? 0)],
            ];
        @endphp

        @foreach ($cards as $card)
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <p class="text-sm text-gray-500">{{ $card['label'] }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $card['value'] }}</p>
                <p class="mt-1 text-xs text-gray-400">{{ $card['hint'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Tren pemindaian --}}
    <x-filament::section heading="Tren Pemindaian QR (14 Hari)" class="mt-6">
        @php $max = $this->maxTrend(); @endphp
        <div class="flex h-40 items-end gap-1.5">
            @foreach ($trend as $day)
                @php $height = (int) round(($day['count'] / $max) * 100); @endphp
                <div class="flex flex-1 flex-col items-center justify-end gap-1">
                    <span class="text-[10px] text-gray-500">{{ $day['count'] > 0 ? $day['count'] : '' }}</span>
                    <div class="w-full rounded-t bg-primary-500"
                         style="height: {{ max($day['count'] > 0 ? 4 : 1, $height) }}%"
                         title="{{ $day['label'] }}: {{ $day['count'] }} pindai"></div>
                    <span class="text-[10px] text-gray-400">{{ $day['label'] }}</span>
                </div>
            @endforeach
        </div>
    </x-filament::section>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Konten terpopuler (QR) --}}
        <x-filament::section heading="Konten Paling Banyak Dipindai">
            @forelse ($topScanned as $item)
                <div class="flex items-center justify-between border-b border-gray-100 py-2 last:border-0">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-gray-900">{{ $item['title'] }}</p>
                        <p class="text-xs text-gray-500">{{ $item['type'] }}</p>
                    </div>
                    <x-filament::badge color="primary">{{ number_format($item['scans']) }}</x-filament::badge>
                </div>
            @empty
                <p class="py-4 text-sm text-gray-500">Belum ada pemindaian pada periode ini.</p>
            @endforelse
        </x-filament::section>

        {{-- Rating tertinggi --}}
        <x-filament::section heading="Rating Tertinggi">
            @forelse ($topRated as $item)
                <div class="flex items-center justify-between border-b border-gray-100 py-2 last:border-0">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-gray-900">{{ $item['title'] }}</p>
                        <p class="text-xs text-gray-500">{{ $item['type'] }} &middot; {{ $item['reviews'] }} ulasan</p>
                    </div>
                    <x-filament::badge color="success">★ {{ $item['rating'] }}</x-filament::badge>
                </div>
            @empty
                <p class="py-4 text-sm text-gray-500">Belum ada ulasan terbit.</p>
            @endforelse
        </x-filament::section>

        {{-- Paling difavoritkan --}}
        <x-filament::section heading="Paling Banyak Difavoritkan">
            @forelse ($topFavorited as $item)
                <div class="flex items-center justify-between border-b border-gray-100 py-2 last:border-0">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-gray-900">{{ $item['title'] }}</p>
                        <p class="text-xs text-gray-500">{{ $item['type'] }}</p>
                    </div>
                    <x-filament::badge color="danger">{{ number_format($item['favorites']) }}</x-filament::badge>
                </div>
            @empty
                <p class="py-4 text-sm text-gray-500">Belum ada favorit tercatat.</p>
            @endforelse
        </x-filament::section>

        {{-- Kata kunci AI --}}
        <x-filament::section heading="Topik Populer di AI Trip Planner">
            @forelse ($topKeywords as $item)
                <div class="flex items-center justify-between border-b border-gray-100 py-2 last:border-0">
                    <p class="text-sm font-medium text-gray-900">{{ $item['keyword'] }}</p>
                    <x-filament::badge color="gray">{{ number_format($item['total']) }}×</x-filament::badge>
                </div>
            @empty
                <p class="py-4 text-sm text-gray-500">Belum ada pertanyaan pada periode ini.</p>
            @endforelse
        </x-filament::section>
    </div>

    {{-- Rincian konten per modul --}}
    <x-filament::section heading="Rincian Konten per Modul" class="mt-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left text-gray-500">
                        <th class="py-2 pr-4 font-medium">Modul</th>
                        <th class="py-2 pr-4 font-medium">Total</th>
                        <th class="py-2 pr-4 font-medium">Terbit</th>
                        <th class="py-2 pr-4 font-medium">Menunggu</th>
                        <th class="py-2 font-medium">Ditolak</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($breakdown as $row)
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="py-2 pr-4 font-medium text-gray-900">{{ $row['label'] }}</td>
                            <td class="py-2 pr-4 text-gray-700">{{ number_format($row['total']) }}</td>
                            <td class="py-2 pr-4 text-success-600">{{ number_format($row['published']) }}</td>
                            <td class="py-2 pr-4 text-warning-600">{{ number_format($row['pending']) }}</td>
                            <td class="py-2 text-danger-600">{{ number_format($row['rejected']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
