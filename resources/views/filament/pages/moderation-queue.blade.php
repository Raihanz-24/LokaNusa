<x-filament-panels::page>
    <div class="mb-4 flex flex-wrap items-center gap-2">
        @foreach ($this->getTypeOptions() as $value => $label)
            <button
                type="button"
                wire:click="setType('{{ $value }}')"
                @class([
                    'rounded-full border px-4 py-1.5 text-sm font-medium transition',
                    'border-primary-600 bg-primary-600 text-white' => $type === $value,
                    'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' => $type !== $value,
                ])
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    @php $items = $this->getItems(); @endphp

    @if ($items->isEmpty())
        <x-filament::section>
            <div class="py-10 text-center">
                <x-filament::icon icon="heroicon-o-check-badge" class="mx-auto h-12 w-12 text-success-500" />
                <p class="mt-3 text-base font-semibold text-gray-900">Antrean kosong</p>
                <p class="mt-1 text-sm text-gray-500">
                    Tidak ada konten yang menunggu review saat ini.
                </p>
            </div>
        </x-filament::section>
    @else
        <div class="space-y-3">
            @foreach ($items as $item)
                <x-filament::section>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-filament::badge color="gray">{{ $item['label'] }}</x-filament::badge>
                                <a href="{{ $item['url'] }}"
                                   class="truncate text-base font-semibold text-primary-600 hover:underline">
                                    {{ $item['title'] }}
                                </a>
                            </div>

                            <p class="mt-1 text-sm text-gray-500">
                                Diajukan oleh: {{ $item['owner'] ?? '—' }}
                                @if ($item['submitted_at'])
                                    &middot; {{ \Illuminate\Support\Carbon::parse($item['submitted_at'])->diffForHumans() }}
                                @endif
                            </p>
                        </div>

                        <div class="flex shrink-0 flex-wrap gap-2">
                            <x-filament::button
                                color="success"
                                icon="heroicon-o-check"
                                wire:click="approve('{{ $item['type'] }}', {{ $item['model']->getKey() }})"
                                wire:confirm="Terbitkan konten ini?"
                            >
                                Setujui
                            </x-filament::button>

                            <x-filament::button
                                color="danger"
                                icon="heroicon-o-x-mark"
                                wire:click="startReject('{{ $item['type'] }}', {{ $item['model']->getKey() }})"
                            >
                                Tolak
                            </x-filament::button>
                        </div>
                    </div>

                    @if ($rejectingType === $item['type'] && $rejectingId === $item['model']->getKey())
                        <div class="mt-4 rounded-xl border border-danger-200 bg-danger-50 p-4">
                            <label for="reason" class="block text-sm font-medium text-gray-900">
                                Alasan penolakan
                            </label>

                            <textarea
                                id="reason"
                                wire:model="reason"
                                rows="3"
                                maxlength="500"
                                class="mt-2 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-danger-500 focus:ring-danger-500"
                                placeholder="Jelaskan bagian yang perlu diperbaiki..."
                            ></textarea>

                            @error('reason')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror

                            <div class="mt-3 flex gap-2">
                                <x-filament::button color="danger" wire:click="reject">
                                    Kirim penolakan
                                </x-filament::button>

                                <x-filament::button color="gray" wire:click="cancelReject">
                                    Batal
                                </x-filament::button>
                            </div>
                        </div>
                    @endif
                </x-filament::section>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
