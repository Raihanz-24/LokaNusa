@props(['name' => 'bank'])

@php
    // Logo ringkas (bukan logo resmi) untuk tampilan metode pembayaran demo.
    $brand = match ($name) {
        'bca' => ['bg' => '#ffffff', 'bd' => '#dbe3ee', 'fg' => '#0060AF', 'text' => 'BCA', 'size' => 21, 'weight' => 800, 'accent' => '#E4002B'],
        'bni' => ['bg' => '#ffffff', 'bd' => '#dbe3ee', 'fg' => '#F15A23', 'text' => 'BNI', 'size' => 22, 'weight' => 800, 'accent' => '#005E6A'],
        'bri' => ['bg' => '#00529C', 'bd' => '#00529C', 'fg' => '#ffffff', 'text' => 'BRI', 'size' => 22, 'weight' => 800, 'accent' => '#F9B233'],
        'mandiri' => ['bg' => '#003D79', 'bd' => '#003D79', 'fg' => '#ffffff', 'text' => 'mandiri', 'size' => 17, 'weight' => 700, 'accent' => '#F9B233'],
        'permata' => ['bg' => '#00A19C', 'bd' => '#00A19C', 'fg' => '#ffffff', 'text' => 'Permata', 'size' => 16, 'weight' => 700, 'accent' => '#ffffff'],
        'qris' => ['bg' => '#ffffff', 'bd' => '#dbe3ee', 'fg' => '#111827', 'text' => 'QRIS', 'size' => 18, 'weight' => 800, 'accent' => '#E31E24'],
        'gopay' => ['bg' => '#00AED6', 'bd' => '#00AED6', 'fg' => '#ffffff', 'text' => 'gopay', 'size' => 20, 'weight' => 800, 'accent' => '#ffffff'],
        'ovo' => ['bg' => '#4C3494', 'bd' => '#4C3494', 'fg' => '#ffffff', 'text' => 'OVO', 'size' => 20, 'weight' => 800, 'accent' => '#ffffff'],
        'dana' => ['bg' => '#118EEA', 'bd' => '#118EEA', 'fg' => '#ffffff', 'text' => 'DANA', 'size' => 19, 'weight' => 800, 'accent' => '#ffffff'],
        'shopeepay' => ['bg' => '#EE4D2D', 'bd' => '#EE4D2D', 'fg' => '#ffffff', 'text' => 'ShopeePay', 'size' => 13, 'weight' => 700, 'accent' => '#ffffff'],
        'alfamart' => ['bg' => '#ffffff', 'bd' => '#dbe3ee', 'fg' => '#E31E24', 'text' => 'Alfamart', 'size' => 14, 'weight' => 800, 'accent' => '#F9B233'],
        'indomaret' => ['bg' => '#ffffff', 'bd' => '#dbe3ee', 'fg' => '#00529C', 'text' => 'Indomaret', 'size' => 13, 'weight' => 800, 'accent' => '#E31E24'],
        default => ['bg' => '#0f172a', 'bd' => '#0f172a', 'fg' => '#ffffff', 'text' => 'BANK', 'size' => 16, 'weight' => 700, 'accent' => '#ffffff'],
    };
@endphp

<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 40" role="img" aria-label="{{ $brand['text'] }}"
     class="{{ $class ?? 'h-7 w-[67px]' }}">
    <rect x="0.5" y="0.5" width="95" height="39" rx="6" fill="{{ $brand['bg'] }}" stroke="{{ $brand['bd'] }}" />

    @if ($name === 'bca')
        <path d="M10 8h13l-2.5 3.5H7.5z" fill="{{ $brand['accent'] }}" />
        <path d="M10 12h10l-2.5 3.5H7.5z" fill="{{ $brand['fg'] }}" opacity=".85" />
        <text x="55" y="28" text-anchor="middle" font-family="Arial, Helvetica, sans-serif"
              font-size="{{ $brand['size'] }}" font-weight="{{ $brand['weight'] }}" fill="{{ $brand['fg'] }}">BCA</text>
    @elseif ($name === 'bni')
        <path d="M8 27c7 4 15 4 22 0" fill="none" stroke="{{ $brand['accent'] }}" stroke-width="2.5" stroke-linecap="round" />
        <text x="55" y="26" text-anchor="middle" font-family="Arial, Helvetica, sans-serif"
              font-size="{{ $brand['size'] }}" font-weight="{{ $brand['weight'] }}" fill="{{ $brand['fg'] }}">BNI</text>
    @elseif ($name === 'bri')
        <path d="M8 30h20" stroke="{{ $brand['accent'] }}" stroke-width="3" stroke-linecap="round" />
        <text x="55" y="26" text-anchor="middle" font-family="Arial, Helvetica, sans-serif"
              font-size="{{ $brand['size'] }}" font-weight="{{ $brand['weight'] }}" fill="{{ $brand['fg'] }}">BRI</text>
    @elseif ($name === 'mandiri')
        <path d="M8 9h4l8 22h-4z" fill="{{ $brand['accent'] }}" />
        <text x="56" y="26" text-anchor="middle" font-family="Arial, Helvetica, sans-serif"
              font-size="{{ $brand['size'] }}" font-weight="{{ $brand['weight'] }}" fill="{{ $brand['fg'] }}">mandiri</text>
    @elseif ($name === 'qris')
        <g fill="#111827">
            <rect x="10" y="10" width="3" height="3" />
            <rect x="15" y="10" width="3" height="3" />
            <rect x="10" y="15" width="3" height="3" />
            <rect x="15" y="15" width="3" height="3" />
            <rect x="10" y="21" width="3" height="3" />
            <rect x="15" y="21" width="3" height="3" />
            <rect x="20" y="13" width="3" height="3" />
            <rect x="20" y="19" width="3" height="3" />
        </g>
        <text x="60" y="27" text-anchor="middle" font-family="Arial, Helvetica, sans-serif"
              font-size="{{ $brand['size'] }}" font-weight="{{ $brand['weight'] }}" fill="{{ $brand['fg'] }}">QRIS</text>
        <circle cx="82" cy="14" r="2.5" fill="{{ $brand['accent'] }}" />
    @else
        <text x="48" y="26" text-anchor="middle" font-family="Arial, Helvetica, sans-serif"
              font-size="{{ $brand['size'] }}" font-weight="{{ $brand['weight'] }}" fill="{{ $brand['fg'] }}">{{ $brand['text'] }}</text>
        @if (in_array($name, ['gopay', 'ovo', 'dana', 'shopeepay', 'permata'], true))
            <circle cx="83" cy="20" r="2.5" fill="{{ $brand['accent'] }}" opacity=".85" />
        @else
            <path d="M8 30h14" stroke="{{ $brand['accent'] }}" stroke-width="2.5" stroke-linecap="round" />
        @endif
    @endif
</svg>
