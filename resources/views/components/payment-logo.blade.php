@props(['name' => 'bank'])

@php
    // Logo resmi metode pembayaran (lihat public/images/payments/ATTRIBUTION.md).
    $logos = [
        'bca' => ['file' => 'bca.svg', 'alt' => 'BCA'],
        'bni' => ['file' => 'bni.svg', 'alt' => 'BNI'],
        'bri' => ['file' => 'bri.svg', 'alt' => 'BRI'],
        'mandiri' => ['file' => 'mandiri.svg', 'alt' => 'Mandiri'],
        'permata' => ['file' => 'permata.svg', 'alt' => 'Permata'],
        'qris' => ['file' => 'qris.svg', 'alt' => 'QRIS'],
        'gopay' => ['file' => 'gopay.svg', 'alt' => 'GoPay'],
        'ovo' => ['file' => 'ovo.svg', 'alt' => 'OVO'],
        'dana' => ['file' => 'dana.svg', 'alt' => 'DANA'],
        'shopeepay' => ['file' => 'shopeepay.svg', 'alt' => 'ShopeePay'],
        'alfamart' => ['file' => 'alfamart.svg', 'alt' => 'Alfamart'],
        'indomaret' => ['file' => 'indomaret.svg', 'alt' => 'Indomaret'],
    ];

    $logo = $logos[$name] ?? null;
@endphp

@if ($logo)
    <img src="{{ asset('images/payments/'.$logo['file']) }}" alt="Logo {{ $logo['alt'] }}"
         loading="lazy" class="max-h-7 w-auto max-w-full object-contain">
@endif
