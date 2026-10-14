<x-filament-panels::page>
    @php
        $content = $summary['content'] ?? [];
        $rating = $summary['rating'] ?? ['average' => null, 'count' => 0];
        $stays = $summary['stays'] ?? ['bookings' => 0, 'nights' => 0, 'revenue' => 0];
        $purchases = $summary['purchases'] ?? ['bookings' => 0, 'units' => 0, 'revenue' => 0];
    @endphp

    @unless ($summary['has_business'] ?? false)
        <x-filament::section>
            <div class="flex flex-col items-start gap-3">
                <x-filament::badge color="warning" icon="heroicon-o-information-circle">
                    Belum ada usaha
                </x-filament::badge>
                <p class="text-sm text-gray-600">
                    Anda belum mengajukan usaha/konten. Ajukan terlebih dahulu agar dapat dipantau dan
                    ditinjau oleh Pengelola LokaNusa.
                </p>
                @if ($this->createUrl())
                    <x-filament::button tag="a" :href="$this->createUrl()" icon="heroicon-o-plus">
                        {{ $this->createLabel() }}
                    </x-filament::button>
                @endif
            </div>
        </x-filament::section>
    @else
        {{-- Ringkasan konten --}}
        <x-filament::section heading="Konten Saya">
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach (['destinasi' => 'Destinasi', 'umkm' => 'UMKM & Kuliner', 'penginapan' => 'Penginapan'] as $key => $label)
                    @php $row = $content[$key] ?? ['total' => 0, 'published' => 0]; @endphp
                    <div class="rounded-xl border border-gray-200 p-4">
                        <p class="text-sm text-gray-500">{{ $label }}</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $row['total'] }}</p>
                        <p class="text-xs text-success-600">{{ $row['published'] }} terbit</p>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Rating & favorit --}}
        <x-filament::section heading="Rating & Favorit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-gray-200 p-4">
                    <p class="text-sm text-gray-500">Rating rata-rata</p>
                    @if ($rating['average'] !== null)
                        <p class="text-2xl font-bold text-gray-900">★ {{ number_format($rating['average'], 1) }}</p>
                        <p class="text-xs text-gray-500">dari {{ $rating['count'] }} ulasan terbit</p>
                    @else
                        <p class="text-2xl font-bold text-gray-400">—</p>
                        <p class="text-xs text-gray-500">Belum ada ulasan terbit</p>
                    @endif
                </div>
                <div class="rounded-xl border border-gray-200 p-4">
                    <p class="text-sm text-gray-500">Disimpan sebagai favorit</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($summary['favorites'] ?? 0) }}</p>
                    <p class="text-xs text-gray-500">oleh wisatawan</p>
                </div>
            </div>
        </x-filament::section>

        {{-- Trafik pemesanan (hanya bila modul pemesanan aktif) --}}
        @if ($bookingEnabled)
            <x-filament::section heading="Trafik Pemesanan (Demo)">
                <x-filament::badge color="warning" class="mb-3">Demo — bukan transaksi nyata</x-filament::badge>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border border-gray-200 p-4">
                        <p class="text-sm text-gray-500">Menginap</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stays['bookings'] }}</p>
                        <p class="text-xs text-gray-500">{{ $stays['nights'] }} malam · Rp{{ number_format($stays['revenue'], 0, ',', '.') }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 p-4">
                        <p class="text-sm text-gray-500">Pembelian produk</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $purchases['bookings'] }}</p>
                        <p class="text-xs text-gray-500">{{ $purchases['units'] }} unit · Rp{{ number_format($purchases['revenue'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </x-filament::section>
        @endif

        {{-- Daftar konten --}}
        <x-filament::section heading="Daftar Konten">
            @if (count($items) === 0)
                <p class="text-sm text-gray-500">Belum ada konten.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="pb-2 pr-4">Modul</th>
                                <th class="pb-2 pr-4">Nama</th>
                                <th class="pb-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr class="border-t border-gray-100">
                                    <td class="py-2 pr-4 text-gray-500">{{ $item['module'] }}</td>
                                    <td class="py-2 pr-4 font-medium text-gray-900">{{ $item['name'] }}</td>
                                    <td class="py-2">
                                        <x-filament::badge :color="$item['status']->color()">
                                            {{ $item['status']->label() }}
                                        </x-filament::badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    @endunless
</x-filament-panels::page>
