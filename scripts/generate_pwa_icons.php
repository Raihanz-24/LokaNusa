<?php

/**
 * Generator ikon PWA sekali-pakai (Step 16).
 *
 * Menghasilkan PNG tanpa dependensi eksternal memakai GD. Dijalankan manual:
 *   php scripts/generate_pwa_icons.php
 *
 * Skrip ini hanya untuk pengembangan; berkas hasil ditulis ke public/icons.
 */
$outDir = __DIR__.'/../public/icons';

if (! is_dir($outDir)) {
    mkdir($outDir, 0775, true);
}

/**
 * Gambar marka ikon pada kanvas besar lalu perkecil (agar tepi halus).
 */
function renderIcon(int $size, float $safeRatio = 1.0): GdImage
{
    $scale = 4;
    $canvas = $size * $scale;

    $img = imagecreatetruecolor($canvas, $canvas);
    imagealphablending($img, true);
    imagesavealpha($img, true);

    $emerald = imagecolorallocate($img, 4, 120, 87);       // #047857
    $emeraldDark = imagecolorallocate($img, 3, 94, 69);    // #035e45
    $white = imagecolorallocate($img, 255, 255, 255);
    $sun = imagecolorallocate($img, 251, 191, 36);         // amber-400

    // Latar belakang bergradasi (atas terang → bawah gelap).
    for ($y = 0; $y < $canvas; $y++) {
        $t = $y / $canvas;
        $r = (int) (4 + (3 - 4) * $t);
        $g = (int) (120 + (94 - 120) * $t);
        $b = (int) (87 + (69 - 87) * $t);
        $line = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $canvas, $y, $line);
    }

    // Skala marka terhadap area aman (maskable perlu padding lebih).
    $cx = $canvas / 2;
    $mark = $canvas * 0.62 * $safeRatio;

    // Matahari.
    $sunR = $mark * 0.16;
    imagefilledellipse($img, (int) ($cx + $mark * 0.28), (int) ($canvas / 2 - $mark * 0.24), (int) ($sunR * 2), (int) ($sunR * 2), $sun);

    // Gunung utama (dua puncak).
    $baseY = $canvas / 2 + $mark * 0.34;
    $peakY = $canvas / 2 - $mark * 0.22;
    $midY = $canvas / 2 - $mark * 0.02;

    $mountainLeft = [
        (int) ($cx - $mark * 0.5), (int) $baseY,
        (int) ($cx - $mark * 0.16), (int) $peakY,
        (int) ($cx + $mark * 0.14), (int) $baseY,
    ];
    $mountainRight = [
        (int) ($cx - $mark * 0.02), (int) $baseY,
        (int) ($cx + $mark * 0.26), (int) $midY,
        (int) ($cx + $mark * 0.54), (int) $baseY,
    ];

    imagefilledpolygon($img, $mountainRight, $emeraldDark);
    imagefilledpolygon($img, $mountainLeft, $white);

    // Turunkan resolusi untuk tepi halus.
    $final = imagecreatetruecolor($size, $size);
    imagealphablending($final, false);
    imagesavealpha($final, true);
    imagecopyresampled($final, $img, 0, 0, 0, 0, $size, $size, $canvas, $canvas);

    imagedestroy($img);

    return $final;
}

$targets = [
    'icon-192.png' => 192,
    'icon-512.png' => 512,
    'icon-maskable-512.png' => 512,
    'apple-touch-icon.png' => 180,
];

foreach ($targets as $name => $size) {
    $safe = str_contains($name, 'maskable') ? 0.72 : 0.9;
    $img = renderIcon($size, $safe);
    imagepng($img, $outDir.'/'.$name);
    imagedestroy($img);
    echo "wrote {$name} ({$size}px)\n";
}

echo "Selesai.\n";
