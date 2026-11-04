@props([
    'name',
    'price' => 0,
    'mode' => 'stay',
    'cta' => null,
    'subtitle' => null,
    'unit' => null,
])

@php
    $price = (int) $price;
    $mode = in_array($mode, ['stay', 'ticket', 'product'], true) ? $mode : 'stay';
    $cta = $cta ?? match ($mode) {
        'stay' => 'Pesan Kamar',
        'ticket' => 'Beli Tiket',
        'product' => 'Beli Produk',
    };
    $guestName = auth()->user()->name ?? '';
    $uid = 'dp-'.\Illuminate\Support\Str::random(6);
    $unitLabel = $unit ?: ($mode === 'ticket' ? 'tiket' : 'pcs');
    $priceLabel = match ($mode) {
        'stay' => 'Harga / malam',
        'ticket' => 'Harga tiket',
        'product' => 'Harga satuan',
    };
    $qtyLabel = $mode === 'ticket' ? 'Jumlah tiket' : 'Jumlah';

    // Daftar metode pembayaran (tampilan demo, terinspirasi Snap/Midtrans).
    $paymentGroups = [
        [
            'title' => 'Transfer Virtual Account',
            'items' => [
                ['group' => 'bank', 'id' => 'bca', 'label' => 'BCA Virtual Account', 'logo' => 'bca', 'prefix' => '39001', 'sub' => 'Virtual Account'],
                ['group' => 'bank', 'id' => 'bni', 'label' => 'BNI Virtual Account', 'logo' => 'bni', 'prefix' => '88101', 'sub' => 'Virtual Account'],
                ['group' => 'bank', 'id' => 'bri', 'label' => 'BRI Virtual Account', 'logo' => 'bri', 'prefix' => '77701', 'sub' => 'BRIVA'],
                ['group' => 'bank', 'id' => 'mandiri', 'label' => 'Mandiri Virtual Account', 'logo' => 'mandiri', 'prefix' => '89501', 'sub' => 'Virtual Account'],
                ['group' => 'bank', 'id' => 'permata', 'label' => 'Permata Virtual Account', 'logo' => 'permata', 'prefix' => '85601', 'sub' => 'Virtual Account'],
            ],
        ],
        [
            'title' => 'QRIS',
            'items' => [
                ['group' => 'qris', 'id' => 'qris', 'label' => 'QRIS', 'logo' => 'qris', 'prefix' => '0', 'sub' => 'Semua bank & e-wallet'],
            ],
        ],
        [
            'title' => 'E-Wallet',
            'items' => [
                ['group' => 'ewallet', 'id' => 'gopay', 'label' => 'GoPay', 'logo' => 'gopay', 'prefix' => '70001', 'sub' => 'Saldo GoPay'],
                ['group' => 'ewallet', 'id' => 'ovo', 'label' => 'OVO', 'logo' => 'ovo', 'prefix' => '70002', 'sub' => 'Saldo OVO'],
                ['group' => 'ewallet', 'id' => 'dana', 'label' => 'DANA', 'logo' => 'dana', 'prefix' => '70003', 'sub' => 'Saldo DANA'],
                ['group' => 'ewallet', 'id' => 'shopeepay', 'label' => 'ShopeePay', 'logo' => 'shopeepay', 'prefix' => '70004', 'sub' => 'Saldo ShopeePay'],
            ],
        ],
        [
            'title' => 'Gerai Retail',
            'items' => [
                ['group' => 'retail', 'id' => 'alfamart', 'label' => 'Alfamart', 'logo' => 'alfamart', 'prefix' => '80001', 'sub' => 'Bayar di kasir'],
                ['group' => 'retail', 'id' => 'indomaret', 'label' => 'Indomaret', 'logo' => 'indomaret', 'prefix' => '80002', 'sub' => 'Bayar di kasir'],
            ],
        ],
    ];
@endphp

{{--
    Pembayaran MODE SIMULASI (murni tampilan, tanpa server/admin).
    Tidak menyimpan data, tidak menagih pembayaran. Jelas diberi label SIMULASI.
--}}
<div data-demo-payment-root data-price="{{ $price }}" data-mode="{{ $mode }}" data-unit="{{ $unitLabel }}">
    <button type="button" data-demo-open
            class="w-full rounded-lg bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
        {{ $cta }}
    </button>
    <p class="mt-2 text-center text-xs text-slate-500">
        Simulasi pemesanan &amp; pembayaran — tidak ada transaksi nyata.
    </p>

    <div data-demo-modal class="fixed inset-0 z-50 hidden">
        <div data-demo-backdrop class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>

        <div class="absolute inset-x-0 bottom-0 mx-auto flex max-h-[94vh] w-full flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl sm:inset-0 sm:bottom-auto sm:top-1/2 sm:max-w-2xl sm:-translate-y-1/2 sm:rounded-3xl">
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-semibold text-slate-900">Pembayaran</h2>
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700">SIMULASI</span>
                </div>
                <button type="button" data-demo-close
                        class="grid h-9 w-9 shrink-0 place-items-center rounded-full text-slate-500 hover:bg-slate-100"
                        aria-label="Tutup">
                    <x-heroicon name="x-mark" class="h-5 w-5" />
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto">
                {{-- Langkah 1: detail pesanan --}}
                <div data-demo-step="1" class="space-y-3 p-5">
                    <div>
                        <p class="text-sm text-slate-500">Kamu memesan</p>
                        <p class="font-semibold text-slate-900">{{ $name }}</p>
                        @if ($subtitle)
                            <p class="text-sm text-slate-500">{{ $subtitle }}</p>
                        @endif
                    </div>

                    @if ($mode === 'stay')
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-sm font-medium text-slate-700" for="{{ $uid }}-start">Check-in</label>
                                <input id="{{ $uid }}-start" type="date" data-demo-start
                                       class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="text-sm font-medium text-slate-700" for="{{ $uid }}-end">Check-out</label>
                                <input id="{{ $uid }}-end" type="date" data-demo-end
                                       class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                        </div>
                    @elseif ($mode === 'ticket')
                        <div>
                            <label class="text-sm font-medium text-slate-700" for="{{ $uid }}-date">Tanggal kunjungan</label>
                            <input id="{{ $uid }}-date" type="date" data-demo-date
                                   class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    @endif

                    @if (in_array($mode, ['ticket', 'product'], true))
                        <div>
                            <label class="text-sm font-medium text-slate-700" for="{{ $uid }}-qty">{{ $qtyLabel }}</label>
                            <input id="{{ $uid }}-qty" type="number" data-demo-qty value="1"
                                   min="1" max="{{ $mode === 'ticket' ? 10 : 100 }}"
                                   class="mt-1 w-28 rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    @endif

                    <div>
                        <label class="text-sm font-medium text-slate-700" for="{{ $uid }}-name">Nama pemesan</label>
                        <input id="{{ $uid }}-name" type="text" data-demo-name maxlength="100"
                               value="{{ $guestName }}" placeholder="Nama lengkap"
                               class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700" for="{{ $uid }}-phone">No. HP / WhatsApp</label>
                        <input id="{{ $uid }}-phone" type="tel" data-demo-phone maxlength="30"
                               placeholder="08xxxxxxxxxx"
                               class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div class="rounded-xl bg-slate-50 p-3 text-sm">
                        <div class="flex items-center justify-between text-slate-600">
                            <span>{{ $priceLabel }}</span>
                            <span class="font-medium text-slate-800">
                                {{ $price > 0 ? 'Rp'.number_format($price, 0, ',', '.') : 'Belum tersedia' }}
                            </span>
                        </div>
                        <div class="mt-1 flex items-center justify-between text-slate-600">
                            <span data-demo-qty-label>{{ $mode === 'stay' ? 'Jumlah malam' : $qtyLabel }}</span>
                            <span class="font-medium text-slate-800" data-demo-qty-value>—</span>
                        </div>
                        <div class="mt-2 flex items-center justify-between border-t border-slate-200 pt-2">
                            <span class="font-semibold text-slate-700">Total (simulasi)</span>
                            <span class="font-bold text-emerald-700" data-demo-total>—</span>
                        </div>
                    </div>

                    <p data-demo-error class="hidden rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700"></p>

                    <button type="button" data-demo-next
                            class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Lanjut Pilih Pembayaran
                    </button>
                </div>

                {{-- Langkah 2: daftar metode pembayaran --}}
                <div data-demo-step="2" class="hidden">
                    <div class="border-b border-slate-100 bg-slate-50 px-5 py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs text-slate-500">Order ID</p>
                                <p class="font-mono text-sm font-semibold text-slate-800" data-demo-code>—</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-slate-500">Total Pembayaran</p>
                                <p class="text-lg font-bold text-slate-900" data-demo-total-2>—</p>
                            </div>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-500" data-demo-summary>—</p>
                    </div>

                    <div class="space-y-5 p-5">
                        @foreach ($paymentGroups as $group)
                            <div>
                                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $group['title'] }}</h3>
                                <div class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200">
                                    @foreach ($group['items'] as $item)
                                        <button type="button" data-demo-method
                                                data-group="{{ $item['group'] }}"
                                                data-provider="{{ $item['id'] }}"
                                                data-prefix="{{ $item['prefix'] }}"
                                                data-label="{{ $item['label'] }}"
                                                class="flex w-full items-center gap-3 bg-white px-4 py-3 text-left transition hover:bg-slate-50">
                                            <span class="flex h-10 w-24 shrink-0 items-center">
                                                <x-payment-logo :name="$item['logo']" />
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm font-medium text-slate-800">{{ $item['label'] }}</span>
                                                <span class="block text-xs text-slate-400">{{ $item['sub'] }}</span>
                                            </span>
                                            <x-heroicon name="chevron-right" class="h-4 w-4 shrink-0 text-slate-400" />
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <p class="rounded-lg bg-amber-50 px-3 py-2 text-center text-xs text-amber-700">
                            Mode <strong>SIMULASI</strong> — pilih metode mana pun. Tidak ada tagihan nyata.
                        </p>
                    </div>

                    <div class="sticky bottom-0 border-t border-slate-100 bg-white p-4">
                        <button type="button" data-demo-back
                                class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                            Kembali
                        </button>
                    </div>
                </div>

                {{-- Langkah 3: instruksi pembayaran --}}
                <div data-demo-step="3" class="hidden p-5">
                    <button type="button" data-demo-back-methods
                            class="inline-flex items-center gap-1 text-sm font-medium text-emerald-700 hover:underline">
                        <x-heroicon name="chevron-right" class="h-4 w-4 rotate-180" />
                        Pilih metode lain
                    </button>

                    <div class="mt-3 flex items-center gap-2 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        <x-heroicon name="clock" class="h-4 w-4 shrink-0 text-slate-500" />
                        <span>Selesaikan pembayaran dalam</span>
                        <span class="font-mono font-bold text-slate-900" data-demo-timer>15:00</span>
                    </div>

                    <div class="mt-5 text-center">
                        <p class="text-xs text-slate-500">Total Pembayaran</p>
                        <p class="text-2xl font-bold text-slate-900" data-demo-amount>—</p>
                        <p class="mt-1 font-mono text-xs text-slate-400" data-demo-code-3>—</p>
                    </div>

                    <div class="mt-5 rounded-xl border border-slate-200">
                        <div class="flex items-center gap-3 border-b border-slate-100 p-4">
                            <span class="flex h-10 w-24 shrink-0 items-center" data-demo-method-logo></span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-800" data-demo-method-label>—</p>
                                <p class="text-xs text-slate-500" data-demo-method-sub>—</p>
                            </div>
                        </div>

                        <div data-demo-number-box class="p-4 text-center">
                            <p class="text-xs text-slate-500" data-demo-number-label>Nomor Virtual Account</p>
                            <p class="mt-1 break-all font-mono text-xl font-bold tracking-wider text-slate-900" data-demo-number>—</p>
                            <button type="button" data-demo-copy
                                    class="mt-2 inline-flex items-center gap-1.5 rounded-lg border border-emerald-300 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">
                                <x-heroicon name="clipboard" class="h-3.5 w-3.5" />
                                <span>Salin Nomor</span>
                            </button>
                            <p class="mt-2 text-xs text-slate-400">a.n. LokaNusa</p>
                        </div>

                        <div data-demo-qris-box class="hidden p-4 text-center">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">QRIS</p>
                            <div class="mx-auto mt-2 grid aspect-square w-44 overflow-hidden rounded-lg border border-slate-200 bg-white p-1.5" data-demo-qris></div>
                            <p class="mt-2 text-xs text-slate-500">Pindai dengan aplikasi bank atau e-wallet apa pun.</p>
                        </div>
                    </div>

                    <div class="mt-6">
                        <h3 class="text-sm font-semibold text-slate-900">Cara Pembayaran</h3>
                        <ol data-demo-instructions class="mt-2 list-decimal space-y-1.5 pl-5 text-sm text-slate-600"></ol>
                    </div>

                    <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-center text-xs text-amber-700">
                        Ini hanya <strong>simulasi</strong>. Nomor di atas fiktif dan tidak dapat dibayar sungguhan.
                    </p>

                    <button type="button" data-demo-paid
                            class="mt-4 w-full rounded-lg bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Saya Sudah Bayar
                    </button>
                </div>

                {{-- Langkah 4: berhasil --}}
                <div data-demo-step="4" class="hidden p-6 text-center">
                    <div class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-emerald-100">
                        <x-heroicon name="check-circle" class="h-10 w-10 text-emerald-600" />
                    </div>
                    <h2 class="mt-3 text-xl font-bold text-slate-900">Pembayaran Berhasil</h2>
                    <p class="mt-1 text-sm text-slate-500">Status pesanan: <span class="font-semibold text-emerald-700">Lunas (simulasi)</span></p>
                    <p class="mt-3 text-2xl font-bold text-slate-900" data-demo-amount-2>—</p>

                    <dl class="mt-5 space-y-2 rounded-xl border border-slate-200 p-4 text-left text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Order ID</dt>
                            <dd class="font-mono font-medium text-slate-800" data-demo-code-4>—</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Metode</dt>
                            <dd class="text-right font-medium text-slate-800" data-demo-method-4>—</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Rincian</dt>
                            <dd class="text-right font-medium text-slate-800" data-demo-summary-4>—</dd>
                        </div>
                    </dl>

                    <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">
                        Tidak ada pesanan tersimpan dan tidak ada tagihan nyata. Ini hanya tampilan simulasi.
                    </p>

                    <button type="button" data-demo-close
                            class="mt-4 w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                        Selesai
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    <script>
        (function () {
            var INSTRUCTIONS = {
                bank: [
                    'Buka aplikasi mobile banking atau ATM bank terkait.',
                    'Pilih menu Transfer / Virtual Account.',
                    'Masukkan nomor Virtual Account di atas.',
                    'Periksa nominal tagihan, lalu konfirmasi pembayaran.'
                ],
                qris: [
                    'Buka aplikasi bank atau e-wallet yang mendukung QRIS.',
                    'Pilih menu Scan / Bayar QRIS.',
                    'Pindai kode QR di atas.',
                    'Periksa nominal, lalu konfirmasi pembayaran.'
                ],
                ewallet: [
                    'Buka aplikasi e-wallet yang dipilih.',
                    'Pilih menu Bayar / Transfer.',
                    'Masukkan nomor pembayaran di atas.',
                    'Periksa nominal, lalu konfirmasi pembayaran.'
                ],
                retail: [
                    'Datang ke gerai retail terdekat.',
                    'Sebutkan kode pembayaran di atas ke kasir.',
                    'Bayar sesuai nominal tagihan.',
                    'Simpan struk pembayaran sebagai bukti.'
                ]
            };

            function rupiah(n) {
                return 'Rp' + Number(n).toLocaleString('id-ID');
            }

            function fmtDate(value) {
                if (!value) return '-';
                var d = new Date(value + 'T00:00:00');
                return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            }

            function randomDigits(n) {
                var out = '';
                for (var i = 0; i < n; i++) out += Math.floor(Math.random() * 10);
                return out;
            }

            function groupDigits(value) {
                return value.replace(/(\d{4})(?=\d)/g, '$1 ');
            }

            function init(root) {
                if (root.dataset.ready === '1') return;
                root.dataset.ready = '1';

                var price = Number(root.dataset.price || 0);
                var mode = root.dataset.mode || 'stay';
                var unit = root.dataset.unit || 'pcs';
                var modal = root.querySelector('[data-demo-modal]');
                var scroller = modal.querySelector('.overflow-y-auto');
                var steps = {
                    1: root.querySelector('[data-demo-step="1"]'),
                    2: root.querySelector('[data-demo-step="2"]'),
                    3: root.querySelector('[data-demo-step="3"]'),
                    4: root.querySelector('[data-demo-step="4"]')
                };
                var start = root.querySelector('[data-demo-start]');
                var end = root.querySelector('[data-demo-end]');
                var date = root.querySelector('[data-demo-date]');
                var qty = root.querySelector('[data-demo-qty]');
                var name = root.querySelector('[data-demo-name]');
                var phone = root.querySelector('[data-demo-phone]');
                var qtyEl = root.querySelector('[data-demo-qty-value]');
                var totalEl = root.querySelector('[data-demo-total]');
                var errorEl = steps[1].querySelector('[data-demo-error]');

                var timerId = null;
                var remaining = 15 * 60;
                var current = null;

                function localISO(d) {
                    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                }

                var today = new Date();
                var tomorrow = new Date(today.getTime() + 86400000);

                if (start && end) {
                    start.min = localISO(today);
                    end.min = localISO(tomorrow);
                    start.value = localISO(today);
                    end.value = localISO(tomorrow);
                }
                if (date) {
                    date.min = localISO(today);
                    date.value = localISO(today);
                }

                function nights() {
                    var a = new Date(start.value + 'T00:00:00');
                    var b = new Date(end.value + 'T00:00:00');
                    var n = Math.round((b - a) / 86400000);
                    return isNaN(n) ? 0 : n;
                }

                function units() {
                    if (mode === 'stay') return nights();
                    return qty ? Math.max(0, parseInt(qty.value, 10) || 0) : 1;
                }

                function amount() {
                    return units() * price;
                }

                function unitText(n) {
                    if (mode === 'stay') return n + ' malam';
                    return n + ' ' + unit;
                }

                function recalc() {
                    var n = units();
                    qtyEl.textContent = n > 0 ? unitText(n) : '—';
                    totalEl.textContent = n > 0 && price > 0 ? rupiah(n * price) : '—';
                }

                if (start) {
                    start.addEventListener('change', function () {
                        if (end.value <= start.value) {
                            var next = new Date(start.value + 'T00:00:00');
                            next.setDate(next.getDate() + 1);
                            end.value = localISO(next);
                        }
                        var minEnd = new Date(start.value + 'T00:00:00');
                        minEnd.setDate(minEnd.getDate() + 1);
                        end.min = localISO(minEnd);
                        recalc();
                    });
                }
                if (end) end.addEventListener('change', recalc);
                if (qty) qty.addEventListener('input', recalc);
                recalc();

                function show(step) {
                    Object.keys(steps).forEach(function (key) {
                        steps[key].classList.toggle('hidden', Number(key) !== step);
                    });
                    if (scroller) scroller.scrollTop = 0;
                }

                function open() {
                    modal.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                    show(1);
                }

                function stopTimer() {
                    if (timerId) {
                        clearInterval(timerId);
                        timerId = null;
                    }
                }

                function reset() {
                    stopTimer();
                    remaining = 15 * 60;
                    current = null;
                    root.querySelector('[data-demo-code]').textContent = '—';
                    root.querySelector('[data-demo-code-3]').textContent = '—';
                    root.querySelector('[data-demo-timer]').textContent = '15:00';
                    root.querySelectorAll('[data-demo-method]').forEach(function (row) {
                        row.classList.remove('bg-emerald-50');
                    });
                }

                function close() {
                    modal.classList.add('hidden');
                    document.body.style.overflow = '';
                    reset();
                    show(1);
                }

                function fail(message) {
                    errorEl.textContent = message;
                    errorEl.classList.remove('hidden');
                }

                function summary() {
                    var n = units();
                    if (mode === 'stay') {
                        return fmtDate(start.value) + ' – ' + fmtDate(end.value) + ' · ' + n + ' malam';
                    }
                    if (mode === 'ticket') {
                        return fmtDate(date.value) + ' · ' + n + ' tiket';
                    }
                    return n + ' ' + unit;
                }

                function newCode() {
                    return 'LK-SIM-' + randomDigits(6);
                }

                root.querySelector('[data-demo-open]').addEventListener('click', open);
                root.querySelectorAll('[data-demo-close]').forEach(function (el) {
                    el.addEventListener('click', close);
                });
                root.querySelector('[data-demo-backdrop]').addEventListener('click', close);
                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && !modal.classList.contains('hidden')) close();
                });

                root.querySelector('[data-demo-next]').addEventListener('click', function () {
                    errorEl.classList.add('hidden');
                    if (mode === 'stay' && nights() < 1) return fail('Tanggal check-out harus setelah check-in.');
                    if (mode !== 'stay' && units() < 1) return fail('Jumlah harus minimal 1.');
                    if (mode === 'ticket' && !date.value) return fail('Tanggal kunjungan wajib diisi.');
                    if (!name.value.trim()) return fail('Nama pemesan wajib diisi.');
                    if (!phone.value.trim() || phone.value.replace(/\D/g, '').length < 8) return fail('Nomor HP/WhatsApp tidak valid.');

                    var code = newCode();
                    root.dataset.code = code;
                    root.querySelector('[data-demo-code]').textContent = code;
                    root.querySelector('[data-demo-total-2]').textContent = price > 0 ? rupiah(amount()) : 'Harga belum tersedia';
                    root.querySelector('[data-demo-summary]').textContent = summary();
                    show(2);
                });

                root.querySelector('[data-demo-back]').addEventListener('click', function () {
                    show(1);
                });

                root.querySelector('[data-demo-back-methods]').addEventListener('click', function () {
                    stopTimer();
                    show(2);
                });

                root.querySelectorAll('[data-demo-method]').forEach(function (row) {
                    row.addEventListener('click', function () {
                        root.querySelectorAll('[data-demo-method]').forEach(function (other) {
                            other.classList.remove('bg-emerald-50');
                        });
                        row.classList.add('bg-emerald-50');
                        current = row;
                        buildPayment(row);
                        show(3);
                    });
                });

                function startTimer() {
                    stopTimer();
                    remaining = 15 * 60;
                    var timerEl = root.querySelector('[data-demo-timer]');

                    function paint() {
                        var minutes = Math.floor(remaining / 60);
                        var seconds = remaining % 60;
                        timerEl.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                    }

                    paint();
                    timerId = setInterval(function () {
                        remaining -= 1;
                        if (remaining <= 0) {
                            remaining = 0;
                            paint();
                            stopTimer();
                            return;
                        }
                        paint();
                    }, 1000);
                }

                function buildPayment(row) {
                    var group = row.dataset.group;
                    var provider = row.dataset.provider;
                    var prefix = row.dataset.prefix || '';
                    var label = row.dataset.label;

                    root.querySelector('[data-demo-amount]').textContent = price > 0 ? rupiah(amount()) : 'Harga belum tersedia';
                    root.querySelector('[data-demo-code-3]').textContent = root.dataset.code || '';
                    root.querySelector('[data-demo-method-logo]').innerHTML = row.querySelector('img').outerHTML;
                    root.querySelector('[data-demo-method-label]').textContent = label;
                    root.querySelector('[data-demo-method-sub]').textContent = group === 'bank'
                        ? 'Virtual Account'
                        : (group === 'qris' ? 'Scan kode QR' : (group === 'retail' ? 'Kode pembayaran' : 'Nomor pembayaran'));

                    var numberBox = root.querySelector('[data-demo-number-box]');
                    var qrisBox = root.querySelector('[data-demo-qris-box]');
                    var numberEl = root.querySelector('[data-demo-number]');

                    if (group === 'qris') {
                        numberBox.classList.add('hidden');
                        qrisBox.classList.remove('hidden');
                        renderQris(root.querySelector('[data-demo-qris]'), root.dataset.code || label);
                    } else {
                        qrisBox.classList.add('hidden');
                        numberBox.classList.remove('hidden');

                        var digits;
                        if (group === 'bank') {
                            digits = (prefix + randomDigits(11)).slice(0, 16);
                            root.querySelector('[data-demo-number-label]').textContent = 'Nomor Virtual Account';
                        } else if (group === 'retail') {
                            digits = (prefix + randomDigits(9)).slice(0, 14);
                            root.querySelector('[data-demo-number-label]').textContent = 'Kode Pembayaran';
                        } else {
                            digits = (prefix + randomDigits(9)).slice(0, 14);
                            root.querySelector('[data-demo-number-label]').textContent = 'Nomor Pembayaran';
                        }

                        numberEl.dataset.raw = digits;
                        numberEl.textContent = groupDigits(digits);
                    }

                    var list = root.querySelector('[data-demo-instructions]');
                    list.innerHTML = '';
                    (INSTRUCTIONS[group] || INSTRUCTIONS.bank).forEach(function (text) {
                        var li = document.createElement('li');
                        li.textContent = text;
                        list.appendChild(li);
                    });

                    startTimer();
                }

                function renderQris(container, seed) {
                    var size = 25;
                    var cells = [];
                    var hash = 0;
                    for (var i = 0; i < seed.length; i++) hash = (hash * 31 + seed.charCodeAt(i)) % 100000;

                    function rnd() {
                        hash = (hash * 1103515245 + 12345) % 2147483648;
                        return hash;
                    }

                    for (var c = 0; c < size * size; c++) cells[c] = (rnd() % 100) < 47;

                    function set(x, y, value) {
                        if (x >= 0 && x < size && y >= 0 && y < size) cells[y * size + x] = value;
                    }

                    function finder(ox, oy) {
                        for (var y = 0; y < 8; y++) {
                            for (var x = 0; x < 8; x++) set(ox + x, oy + y, false);
                        }
                        for (var fy = 0; fy < 7; fy++) {
                            for (var fx = 0; fx < 7; fx++) {
                                var border = fx === 0 || fx === 6 || fy === 0 || fy === 6;
                                var center = fx >= 2 && fx <= 4 && fy >= 2 && fy <= 4;
                                set(ox + fx, oy + fy, border || center);
                            }
                        }
                    }

                    finder(0, 0);
                    finder(size - 7, 0);
                    finder(0, size - 7);

                    container.innerHTML = '';
                    container.style.gridTemplateColumns = 'repeat(' + size + ', minmax(0, 1fr))';
                    container.style.gridTemplateRows = 'repeat(' + size + ', minmax(0, 1fr))';

                    cells.forEach(function (dark) {
                        var cell = document.createElement('span');
                        cell.style.backgroundColor = dark ? '#0f172a' : '#ffffff';
                        container.appendChild(cell);
                    });
                }

                root.querySelector('[data-demo-copy]').addEventListener('click', function () {
                    var el = root.querySelector('[data-demo-number]');
                    var value = (el.dataset.raw || el.textContent).replace(/\s/g, '');
                    var button = this;

                    function done() {
                        var label = button.querySelector('span');
                        var old = label ? label.textContent : button.textContent;
                        if (label) label.textContent = 'Tersalin';
                        else button.textContent = 'Tersalin';
                        setTimeout(function () {
                            if (label) label.textContent = old;
                            else button.textContent = old;
                        }, 1500);
                    }

                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(value).then(done).catch(done);
                    } else {
                        var input = document.createElement('input');
                        input.value = value;
                        document.body.appendChild(input);
                        input.select();
                        try { document.execCommand('copy'); } catch (e) {}
                        document.body.removeChild(input);
                        done();
                    }
                });

                root.querySelector('[data-demo-paid]').addEventListener('click', function () {
                    stopTimer();
                    var label = current ? current.dataset.label : '—';
                    root.querySelector('[data-demo-amount-2]').textContent = price > 0 ? rupiah(amount()) : 'Harga belum tersedia';
                    root.querySelector('[data-demo-code-4]').textContent = root.dataset.code || '—';
                    root.querySelector('[data-demo-method-4]').textContent = label;
                    root.querySelector('[data-demo-summary-4]').textContent = summary();
                    show(4);
                });
            }

            function boot() {
                document.querySelectorAll('[data-demo-payment-root]').forEach(init);
            }

            document.addEventListener('DOMContentLoaded', boot);
        })();
    </script>
@endonce
