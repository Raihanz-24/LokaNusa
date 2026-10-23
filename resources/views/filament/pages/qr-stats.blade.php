<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Kartu QR --}}
        <x-filament::section heading="Kode QR" class="lg:col-span-1">
            <div class="flex flex-col items-center gap-4">
                <img src="{{ $qrImage }}" alt="QR {{ $contentTitle }}"
                     class="h-64 w-64 rounded-xl border border-gray-200 bg-white p-2">

                <p class="break-all text-center text-xs text-gray-500">{{ $qrUrl }}</p>

                <x-filament::badge color="gray">{{ $contentLabel }}</x-filament::badge>

                <div class="flex flex-wrap justify-center gap-2">
                    <x-filament::button tag="a" :href="route('qr.image', $record->qrToken())"
                                        target="_blank" icon="heroicon-o-arrow-down-tray" color="primary">
                        Unduh PNG
                    </x-filament::button>

                    <x-filament::button tag="a" :href="$this->posterUrl()" target="_blank"
                                        icon="heroicon-o-printer" color="success">
                        Papan QR
                    </x-filament::button>

                    <x-filament::button tag="a" :href="$qrUrl" target="_blank"
                                        icon="heroicon-o-arrow-top-right-on-square" color="gray">
                        Uji Tautan
                    </x-filament::button>
                </div>

                @if (! $this->isPublished())
                    <p class="rounded-lg bg-warning-50 px-3 py-2 text-center text-xs text-warning-700">
                        Konten belum terbit — QR baru aktif setelah konten dipublikasikan.
                    </p>
                @else
                    <p class="text-center text-xs text-gray-500">
                        Cetak dan tempel di lokasi. Setiap pindai tercatat sebagai kunjungan.
                    </p>
                @endif
            </div>
        </x-filament::section>

        {{-- Statistik --}}
        <div class="space-y-6 lg:col-span-2">
            <x-filament::section heading="Ringkasan Pemindaian">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-xl border border-gray-200 p-4">
                        <p class="text-sm text-gray-500">Total pindai</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total']) }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 p-4">
                        <p class="text-sm text-gray-500">Hari ini</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['today']) }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 p-4">
                        <p class="text-sm text-gray-500">7 hari terakhir</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['last_7_days']) }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 p-4">
                        <p class="text-sm text-gray-500">30 hari terakhir</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['last_30_days']) }}</p>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border border-gray-200 p-4">
                        <p class="text-sm text-gray-500">Pengunjung terdaftar (unik)</p>
                        <p class="text-xl font-semibold text-gray-900">{{ number_format($stats['unique_visitors']) }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 p-4">
                        <p class="text-sm text-gray-500">Pindai terakhir</p>
                        <p class="text-xl font-semibold text-gray-900">{{ $this->lastScanLabel() }}</p>
                    </div>
                </div>
            </x-filament::section>

            {{-- Grafik batang 14 hari (tanpa library tambahan) --}}
            <x-filament::section heading="Pemindaian 14 Hari Terakhir">
                @php $max = $this->maxDaily(); @endphp
                <div class="flex h-40 items-end gap-1.5">
                    @foreach ($daily as $day)
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

                @if ($stats['total'] === 0)
                    <p class="mt-4 text-sm text-gray-500">
                        Belum ada pemindaian tercatat. Statistik akan terisi setelah QR dipindai pengunjung.
                    </p>
                @endif
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
