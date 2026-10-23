<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Papan QR — {{ $title }}</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #f3f4f6;
            color: #111827;
        }

        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 18mm;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 14px;
            border-top: 14px solid #047857;
        }

        .brand {
            font-size: 15px;
            letter-spacing: .35em;
            font-weight: 700;
            color: #047857;
            text-transform: uppercase;
        }

        .label {
            font-size: 14px;
            font-weight: 600;
            letter-spacing: .18em;
            color: #6b7280;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            font-size: 34px;
            line-height: 1.2;
            max-width: 150mm;
        }

        .qr {
            width: 105mm;
            height: 105mm;
            padding: 6mm;
            border: 2px solid #e5e7eb;
            border-radius: 10mm;
            background: #fff;
        }

        .qr img { width: 100%; height: 100%; display: block; }

        .hint {
            font-size: 20px;
            font-weight: 600;
            color: #047857;
        }

        .sub {
            font-size: 14px;
            color: #6b7280;
            max-width: 130mm;
            line-height: 1.6;
        }

        .url {
            font-size: 12px;
            color: #9ca3af;
            word-break: break-all;
        }

        .toolbar {
            position: fixed;
            top: 16px;
            right: 16px;
            display: flex;
            gap: 8px;
        }

        .toolbar button {
            padding: 10px 18px;
            border: 0;
            border-radius: 8px;
            background: #047857;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        @media print {
            body { background: #fff; }
            .sheet { border: 0; border-top: 14px solid #047857; margin: 0; }
            .toolbar { display: none; }
            @page { size: A4; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Cetak</button>
    </div>

    <div class="sheet">
        <div class="brand">LokaNusa</div>
        <div class="label">{{ $label }}</div>

        <h1>{{ $title }}</h1>

        <div class="qr">
            <img src="{{ $qrImage }}" alt="Kode QR {{ $title }}">
        </div>

        <div class="hint">Pindai untuk melihat detail</div>

        <p class="sub">
            Arahkan kamera ponsel Anda ke kode QR di atas untuk membuka informasi
            lengkap, lokasi, dan ulasan pengunjung.
        </p>

        <div class="url">{{ $publicUrl }}</div>
    </div>
</body>
</html>
