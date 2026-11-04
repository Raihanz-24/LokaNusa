@props(['accommodation'])

@php
    $price = (int) ($accommodation->price_per_night ?? 0);
    $guestName = auth()->user()->name ?? '';
@endphp

{{--
    Pemesanan penginapan MODE DEMO (murni tampilan, tanpa server/admin).
    Tidak menyimpan data, tidak menagih pembayaran. Jelas diberi label DEMO.
--}}
<div data-demo-booking-root data-price="{{ $price }}">
    <button type="button" data-demo-open
            class="w-full rounded-lg bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
        Pesan Kamar
    </button>
    <p class="mt-2 text-center text-xs text-slate-500">
        Simulasi pemesanan &amp; pembayaran (demo) — tidak ada transaksi nyata.
    </p>

    <div data-demo-modal class="fixed inset-0 z-50 hidden">
        <div data-demo-backdrop class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>

        <div class="absolute inset-x-0 bottom-0 mx-auto max-h-[92vh] w-full overflow-y-auto rounded-t-3xl bg-white p-5 shadow-2xl sm:inset-0 sm:bottom-auto sm:top-1/2 sm:max-w-lg sm:-translate-y-1/2 sm:rounded-3xl">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-semibold text-slate-900">Pesan Kamar</h2>
                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700">DEMO</span>
                    </div>
                    <p class="mt-0.5 text-sm text-slate-500">{{ $accommodation->name }}</p>
                </div>
                <button type="button" data-demo-close
                        class="grid h-9 w-9 shrink-0 place-items-center rounded-full text-slate-500 hover:bg-slate-100"
                        aria-label="Tutup">
                    <x-heroicon name="x-mark" class="h-5 w-5" />
                </button>
            </div>

            {{-- Langkah 1: detail menginap --}}
            <div data-demo-step="1" class="mt-4 space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium text-slate-700" for="demo-start-{{ $accommodation->getKey() }}">Check-in</label>
                        <input id="demo-start-{{ $accommodation->getKey() }}" type="date" data-demo-start
                               class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700" for="demo-end-{{ $accommodation->getKey() }}">Check-out</label>
                        <input id="demo-end-{{ $accommodation->getKey() }}" type="date" data-demo-end
                               class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700" for="demo-name-{{ $accommodation->getKey() }}">Nama pemesan</label>
                    <input id="demo-name-{{ $accommodation->getKey() }}" type="text" data-demo-name maxlength="100"
                           value="{{ $guestName }}" placeholder="Nama lengkap"
                           class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700" for="demo-phone-{{ $accommodation->getKey() }}">No. HP / WhatsApp</label>
                    <input id="demo-phone-{{ $accommodation->getKey() }}" type="tel" data-demo-phone maxlength="30"
                           placeholder="08xxxxxxxxxx"
                           class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>

                <div class="rounded-xl bg-slate-50 p-3 text-sm">
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Harga / malam</span>
                        <span class="font-medium text-slate-800">
                            {{ $price > 0 ? 'Rp'.number_format($price, 0, ',', '.') : 'Belum tersedia' }}
                        </span>
                    </div>
                    <div class="mt-1 flex items-center justify-between text-slate-600">
                        <span>Jumlah malam</span>
                        <span class="font-medium text-slate-800" data-demo-nights>—</span>
                    </div>
                    <div class="mt-2 flex items-center justify-between border-t border-slate-200 pt-2">
                        <span class="font-semibold text-slate-700">Total (demo)</span>
                        <span class="font-bold text-emerald-700" data-demo-total>—</span>
                    </div>
                </div>

                <p data-demo-error class="hidden rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700"></p>

                <button type="button" data-demo-next
                        class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                    Lanjut Pilih Pembayaran
                </button>
            </div>

            {{-- Langkah 2: metode pembayaran --}}
            <div data-demo-step="2" class="mt-4 hidden space-y-4">
                <p class="text-sm text-slate-500">Pilih metode pembayaran simulasi:</p>

                <div class="space-y-3">
                    <div class="rounded-xl border border-slate-200 p-3">
                        <p class="mb-2 text-sm font-semibold text-slate-800">Transfer Bank</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach (['bca' => 'BCA', 'bni' => 'BNI', 'bri' => 'BRI', 'mandiri' => 'Mandiri'] as $value => $label)
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:border-emerald-300">
                                    <input type="radio" name="demo_method" value="bank:{{ $value }}" class="text-emerald-600 focus:ring-emerald-500">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-3">
                        <p class="mb-2 text-sm font-semibold text-slate-800">QRIS</p>
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:border-emerald-300">
                            <input type="radio" name="demo_method" value="qris:qris" class="text-emerald-600 focus:ring-emerald-500">
                            QRIS (semua bank &amp; e-wallet)
                        </label>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-3">
                        <p class="mb-2 text-sm font-semibold text-slate-800">E-Wallet</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach (['gopay' => 'GoPay', 'ovo' => 'OVO', 'dana' => 'DANA', 'shopeepay' => 'ShopeePay'] as $value => $label)
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:border-emerald-300">
                                    <input type="radio" name="demo_method" value="ewallet:{{ $value }}" class="text-emerald-600 focus:ring-emerald-500">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <p data-demo-error class="hidden rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700"></p>

                <div class="flex gap-2">
                    <button type="button" data-demo-back
                            class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                        Kembali
                    </button>
                    <button type="button" data-demo-pay
                            class="flex-1 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Buat Pembayaran Demo
                    </button>
                </div>
            </div>

            {{-- Langkah 3: hasil simulasi --}}
            <div data-demo-step="3" class="mt-4 hidden space-y-3">
                <div class="rounded-xl bg-emerald-50 p-4 text-center">
                    <p class="text-sm font-medium text-emerald-800">Pesanan demo berhasil dibuat</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-700" data-demo-code>—</p>
                    <p class="mt-1 text-xs text-emerald-700">Status: Menunggu Pembayaran (simulasi)</p>
                </div>

                <dl class="space-y-2 rounded-xl border border-slate-200 p-4 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Metode</dt>
                        <dd class="text-right font-medium text-slate-800" data-demo-method>—</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Menginap</dt>
                        <dd class="text-right font-medium text-slate-800" data-demo-stay>—</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Total</dt>
                        <dd class="text-right font-bold text-emerald-700" data-demo-amount>—</dd>
                    </div>
                </dl>

                <div data-demo-va-box class="rounded-xl border border-dashed border-emerald-300 bg-emerald-50/50 p-4 text-center">
                    <p class="text-xs font-medium uppercase tracking-wide text-emerald-700" data-demo-va-label>Nomor Virtual Account (Demo)</p>
                    <p class="mt-1 break-all text-xl font-bold tracking-wider text-slate-900" data-demo-va>—</p>
                    <button type="button" data-demo-copy
                            class="mt-2 rounded-lg border border-emerald-300 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">
                        Salin Nomor
                    </button>
                </div>

                <div data-demo-qris-box class="hidden rounded-xl border border-dashed border-emerald-300 bg-white p-4 text-center">
                    <p class="text-xs font-medium uppercase tracking-wide text-emerald-700">QRIS (Demo)</p>
                    <div class="mx-auto mt-2 grid h-32 w-32 grid-cols-8 grid-rows-8 overflow-hidden rounded-lg border border-slate-200" data-demo-qris></div>
                    <p class="mt-2 text-xs text-slate-500">Pindai hanya untuk tampilan — tidak ada tagihan.</p>
                </div>

                <p class="rounded-lg bg-amber-50 px-3 py-2 text-center text-xs text-amber-700">
                    Ini hanya <strong>simulasi</strong>. Tidak ada pesanan tersimpan, tidak ada tagihan, dan tidak terhubung ke admin.
                </p>

                <button type="button" data-demo-close
                        class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                    Selesai
                </button>
            </div>
        </div>
    </div>
</div>

@once
    <script>
        (function () {
            var PREFIX = {
                bca: '39001', bni: '88101', bri: '77701', mandiri: '89501',
                gopay: '70001', ovo: '70002', dana: '70003', shopeepay: '70004'
            };
            var LABEL = {
                bca: 'Transfer BCA', bni: 'Transfer BNI', bri: 'Transfer BRI', mandiri: 'Transfer Mandiri',
                qris: 'QRIS', gopay: 'GoPay', ovo: 'OVO', dana: 'DANA', shopeepay: 'ShopeePay'
            };

            function rupiah(n) {
                return 'Rp' + Number(n).toLocaleString('id-ID');
            }

            function fmtDate(value) {
                if (!value) return '-';
                var d = new Date(value + 'T00:00:00');
                return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            }

            function init(root) {
                if (root.dataset.ready === '1') return;
                root.dataset.ready = '1';

                var price = Number(root.dataset.price || 0);
                var modal = root.querySelector('[data-demo-modal]');
                var steps = {
                    1: root.querySelector('[data-demo-step="1"]'),
                    2: root.querySelector('[data-demo-step="2"]'),
                    3: root.querySelector('[data-demo-step="3"]')
                };
                var start = root.querySelector('[data-demo-start]');
                var end = root.querySelector('[data-demo-end]');
                var name = root.querySelector('[data-demo-name]');
                var phone = root.querySelector('[data-demo-phone]');
                var nightsEl = root.querySelector('[data-demo-nights]');
                var totalEl = root.querySelector('[data-demo-total]');
                var errorEl = steps[1].querySelector('[data-demo-error]');

                function localISO(d) {
                    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                }

                var today = new Date();
                var tomorrow = new Date(today.getTime() + 86400000);
                start.min = localISO(today);
                end.min = localISO(tomorrow);
                start.value = localISO(today);
                end.value = localISO(tomorrow);

                function nights() {
                    var a = new Date(start.value + 'T00:00:00');
                    var b = new Date(end.value + 'T00:00:00');
                    var n = Math.round((b - a) / 86400000);
                    return isNaN(n) ? 0 : n;
                }

                function recalc() {
                    var n = nights();
                    nightsEl.textContent = n > 0 ? n + ' malam' : '—';
                    totalEl.textContent = n > 0 && price > 0 ? rupiah(n * price) : '—';
                }

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
                end.addEventListener('change', recalc);
                recalc();

                function show(step) {
                    Object.keys(steps).forEach(function (key) {
                        steps[key].classList.toggle('hidden', Number(key) !== step);
                    });
                    modal.scrollTop = 0;
                }

                function open() {
                    modal.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                    show(1);
                }

                function close() {
                    modal.classList.add('hidden');
                    document.body.style.overflow = '';
                }

                function fail(message) {
                    errorEl.textContent = message;
                    errorEl.classList.remove('hidden');
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
                    if (nights() < 1) return fail('Tanggal check-out harus setelah check-in.');
                    if (!name.value.trim()) return fail('Nama pemesan wajib diisi.');
                    if (!phone.value.trim() || phone.value.replace(/\D/g, '').length < 8) return fail('Nomor HP/WhatsApp tidak valid.');
                    show(2);
                });

                root.querySelector('[data-demo-back]').addEventListener('click', function () {
                    show(1);
                });

                root.querySelector('[data-demo-pay]').addEventListener('click', function () {
                    var picked = root.querySelector('input[name="demo_method"]:checked');
                    var error2 = steps[2].querySelector('[data-demo-error]');
                    if (!picked) {
                        error2.textContent = 'Silakan pilih metode pembayaran.';
                        error2.classList.remove('hidden');
                        return;
                    }
                    error2.classList.add('hidden');

                    var parts = picked.value.split(':');
                    var group = parts[0];
                    var provider = parts[1];
                    var n = nights();
                    var amount = n * price;
                    var code = 'LK-DEMO-' + String(Math.floor(100000 + Math.random() * 900000));

                    root.querySelector('[data-demo-code]').textContent = code;
                    root.querySelector('[data-demo-method]').textContent = LABEL[provider] || provider;
                    root.querySelector('[data-demo-stay]').textContent = fmtDate(start.value) + ' – ' + fmtDate(end.value) + ' (' + n + ' malam)';
                    root.querySelector('[data-demo-amount]').textContent = price > 0 ? rupiah(amount) : 'Harga belum tersedia';
                    root.querySelector('[data-demo-va-label]').textContent = group === 'qris'
                        ? 'Nomor Referensi (Demo)'
                        : (group === 'ewallet' ? 'Nomor Pembayaran (Demo)' : 'Nomor Virtual Account (Demo)');

                    if (group === 'qris') {
                        root.querySelector('[data-demo-va-box]').classList.add('hidden');
                        root.querySelector('[data-demo-qris-box]').classList.remove('hidden');
                        renderQris(root.querySelector('[data-demo-qris]'), code);
                    } else {
                        var digits = '';
                        for (var i = 0; i < 11; i++) digits += Math.floor(Math.random() * 10);
                        var number = (PREFIX[provider] || '90001') + digits;
                        root.querySelector('[data-demo-qris-box]').classList.add('hidden');
                        root.querySelector('[data-demo-va-box]').classList.remove('hidden');
                        root.querySelector('[data-demo-va]').textContent = number.replace(/(\d{4})(?=\d)/g, '$1 ');
                        root.querySelector('[data-demo-va]').dataset.raw = number;
                    }

                    show(3);
                });

                function renderQris(container, seed) {
                    container.innerHTML = '';
                    var hash = 0;
                    for (var i = 0; i < seed.length; i++) hash = (hash * 31 + seed.charCodeAt(i)) % 100000;
                    for (var c = 0; c < 64; c++) {
                        hash = (hash * 1103515245 + 12345) % 2147483648;
                        var cell = document.createElement('span');
                        cell.className = (hash % 3 === 0) ? 'bg-slate-900' : 'bg-white';
                        container.appendChild(cell);
                    }
                }

                root.querySelector('[data-demo-copy]').addEventListener('click', function () {
                    var el = root.querySelector('[data-demo-va]');
                    var value = (el.dataset.raw || el.textContent).replace(/\s/g, '');
                    var button = this;
                    function done() {
                        var old = button.textContent;
                        button.textContent = 'Tersalin';
                        setTimeout(function () { button.textContent = old; }, 1500);
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
            }

            function boot() {
                document.querySelectorAll('[data-demo-booking-root]').forEach(init);
            }

            document.addEventListener('DOMContentLoaded', boot);
        })();
    </script>
@endonce
