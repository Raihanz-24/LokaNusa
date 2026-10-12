@props(['value' => 0])

{{-- Input rating bintang interaktif. Memilih bintang ke-N akan menyalakan
     semua bintang 1..N agar jelas bagi pengguna. --}}
<div data-star-input class="inline-flex items-center gap-1" role="radiogroup" aria-label="Pilih rating">
    @for ($star = 1; $star <= 5; $star++)
        <button type="button" role="radio" data-star="{{ $star }}"
                aria-checked="{{ (int) $value === $star ? 'true' : 'false' }}"
                aria-label="{{ $star }} bintang"
                class="text-3xl leading-none text-slate-300 transition hover:text-amber-300">★</button>
    @endfor
    <input type="hidden" name="rating" value="{{ (int) $value ?: '' }}" data-star-value>
</div>

@once
    <script>
        (function () {
            function paint(root, value) {
                root.querySelectorAll('[data-star]').forEach(function (btn) {
                    var star = parseInt(btn.dataset.star, 10);
                    btn.classList.toggle('text-amber-400', star <= value);
                    btn.classList.toggle('text-slate-300', star > value);
                    btn.setAttribute('aria-checked', star === value ? 'true' : 'false');
                });
                var hidden = root.querySelector('[data-star-value]');
                if (hidden) hidden.value = value > 0 ? value : '';
            }

            function init() {
                document.querySelectorAll('[data-star-input]').forEach(function (root) {
                    if (root.dataset.ready === '1') return;
                    root.dataset.ready = '1';

                    var hidden = root.querySelector('[data-star-value]');
                    var initial = hidden && hidden.value ? parseInt(hidden.value, 10) : 0;
                    paint(root, initial);

                    root.querySelectorAll('[data-star]').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            paint(root, parseInt(btn.dataset.star, 10));
                        });
                    });
                });
            }

            document.addEventListener('DOMContentLoaded', init);
        })();
    </script>
@endonce
