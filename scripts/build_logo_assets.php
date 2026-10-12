<?php

// Skrip sekali-pakai: mengolah logo sumber (public/images/brand/logo.jpeg)
// menjadi aset siap pakai (PNG transparan, favicon, ikon PWA).
//
// Latar putih dihapus dengan flood-fill dari tepi (toleransi terhadap
// artefak JPEG) sehingga warna putih di dalam emblem TIDAK ikut terhapus.
//
// Jalankan: php scripts/build_logo_assets.php

ini_set('memory_limit', '512M');

$root = dirname(__DIR__);
$sourcePath = $root.'/public/images/brand/logo.jpeg';
$brandDir = $root.'/public/images/brand';
$iconsDir = $root.'/public/icons';

$src = imagecreatefromjpeg($sourcePath);
if (! $src) {
    fwrite(STDERR, "Gagal membuka logo sumber: {$sourcePath}\n");
    exit(1);
}

$w = imagesx($src);
$h = imagesy($src);

// Siapkan kanvas dengan dukungan alpha.
$img = imagecreatetruecolor($w, $h);
imagealphablending($img, false);
imagesavealpha($img, true);
imagecopy($img, $src, 0, 0, 0, 0, $w, $h);
imagedestroy($src);

/**
 * Apakah piksel termasuk latar (putih) dan belum diproses.
 */
$isBackground = function (int $x, int $y) use ($img, $w, $h): bool {
    if ($x < 0 || $y < 0 || $x >= $w || $y >= $h) {
        return false;
    }
    $rgba = imagecolorat($img, $x, $y);
    $alpha = ($rgba >> 24) & 0x7F;
    if ($alpha > 0) {
        return false; // Sudah diproses (ditandai transparan).
    }
    $r = ($rgba >> 16) & 0xFF;
    $g = ($rgba >> 8) & 0xFF;
    $b = $rgba & 0xFF;

    // Putih-ish dengan toleransi untuk artefak kompresi JPEG.
    return $r >= 235 && $g >= 235 && $b >= 235;
};

$transparent = imagecolorallocatealpha($img, 255, 255, 255, 127);

// Flood-fill scanline dari seluruh tepi.
$stack = [];
for ($x = 0; $x < $w; $x++) {
    $stack[] = [$x, 0];
    $stack[] = [$x, $h - 1];
}
for ($y = 0; $y < $h; $y++) {
    $stack[] = [0, $y];
    $stack[] = [$w - 1, $y];
}

$processed = 0;
while ($stack) {
    [$x, $y] = array_pop($stack);
    if (! $isBackground($x, $y)) {
        continue;
    }

    // Cari ujung kiri & kanan pada baris ini.
    $x1 = $x;
    while ($x1 - 1 >= 0 && $isBackground($x1 - 1, $y)) {
        $x1--;
    }
    $x2 = $x;
    while ($x2 + 1 < $w && $isBackground($x2 + 1, $y)) {
        $x2++;
    }

    for ($xi = $x1; $xi <= $x2; $xi++) {
        imagesetpixel($img, $xi, $y, $transparent);
        $processed++;
    }

    // Periksa baris atas & bawah.
    foreach ([$y - 1, $y + 1] as $ny) {
        for ($xi = $x1; $xi <= $x2; $xi++) {
            if ($isBackground($xi, $ny)) {
                $stack[] = [$xi, $ny];
            }
        }
    }
}

echo "Piksel latar dihapus: {$processed}\n";

/**
 * Potong berdasarkan kotak koordinat + padding, kembalikan resource baru.
 */
$crop = function (int $left, int $top, int $right, int $bottom, int $pad = 0) use ($img, $w, $h) {
    $left = max(0, $left - $pad);
    $top = max(0, $top - $pad);
    $right = min($w - 1, $right + $pad);
    $bottom = min($h - 1, $bottom + $pad);

    $cw = $right - $left + 1;
    $ch = $bottom - $top + 1;

    $out = imagecreatetruecolor($cw, $ch);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    $trans = imagecolorallocatealpha($out, 255, 255, 255, 127);
    imagefilledrectangle($out, 0, 0, $cw, $ch, $trans);
    imagecopy($out, $img, 0, 0, $left, $top, $cw, $ch);

    return $out;
};

// Koordinat dari analisis scripts/analyze_logo.php (logo 1254x1254).
$emblem = ['x1' => 322, 'y1' => 120, 'x2' => 932, 'y2' => 732];
$wordmarkBottom = 889;   // emblem + tulisan LOKANUSA
$fullBottom = 938;       // + tagline

/**
 * Skalakan gambar agar lebarnya maksimal $maxWidth (tanpa memperbesar).
 */
$downscale = function ($source, int $maxWidth) {
    $sw = imagesx($source);
    if ($sw <= $maxWidth) {
        return $source;
    }
    $sh = imagesy($source);
    $scale = $maxWidth / $sw;
    $nw = $maxWidth;
    $nh = (int) round($sh * $scale);

    $out = imagecreatetruecolor($nw, $nh);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    $trans = imagecolorallocatealpha($out, 255, 255, 255, 127);
    imagefilledrectangle($out, 0, 0, $nw, $nh, $trans);
    imagealphablending($out, true);
    imagecopyresampled($out, $source, 0, 0, 0, 0, $nw, $nh, $sw, $sh);

    return $out;
};

// 1) Logo utama: emblem + wordmark (dioptimalkan untuk tampilan web).
$logo = $crop(123, 120, 1129, $wordmarkBottom, 6);
$logo = $downscale($logo, 480);
imagepng($logo, $brandDir.'/logo.png', 9);
imagedestroy($logo);

// 2) Logo lengkap: emblem + wordmark + tagline.
$logoFull = $crop(123, 120, 1129, $fullBottom, 6);
$logoFull = $downscale($logoFull, 560);
imagepng($logoFull, $brandDir.'/logo-full.png', 9);
imagedestroy($logoFull);

// 3) Emblem saja (dioptimalkan untuk tampilan; tetap presisi untuk ikon kecil).
$mark = $crop($emblem['x1'], $emblem['y1'], $emblem['x2'], $emblem['y2'], 6);
$mark = $downscale($mark, 256);
imagepng($mark, $brandDir.'/logo-mark.png', 9);

// 4) Emblem kecil untuk header (ukuran file ringan).
$markSm = $downscale($mark, 96);
imagepng($markSm, $brandDir.'/logo-mark-sm.png', 9);
imagedestroy($markSm);

/**
 * Tempelkan gambar sumber ke kanvas persegi dengan latar & skala tertentu.
 */
$renderIcon = function ($source, int $size, array $bg = [255, 255, 255], float $fill = 0.84, bool $opaque = true) {
    $canvas = imagecreatetruecolor($size, $size);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);

    if ($opaque) {
        $bgColor = imagecolorallocate($canvas, $bg[0], $bg[1], $bg[2]);
        imagefilledrectangle($canvas, 0, 0, $size, $size, $bgColor);
    } else {
        $trans = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $size, $size, $trans);
    }

    $sw = imagesx($source);
    $sh = imagesy($source);
    $target = (int) round($size * $fill);
    $scale = min($target / $sw, $target / $sh);
    $nw = (int) round($sw * $scale);
    $nh = (int) round($sh * $scale);
    $dx = (int) round(($size - $nw) / 2);
    $dy = (int) round(($size - $nh) / 2);

    imagealphablending($canvas, true);
    imagecopyresampled($canvas, $source, $dx, $dy, 0, 0, $nw, $nh, $sw, $sh);

    return $canvas;
};

// Favicon PNG (transparan) dari emblem.
$favicon = $renderIcon($mark, 64, [0, 0, 0], 0.92, false);
imagepng($favicon, $root.'/public/favicon.png', 9);

// Apple touch icon (latar putih).
$apple = $renderIcon($mark, 180, [255, 255, 255], 0.84);
imagepng($apple, $iconsDir.'/apple-touch-icon.png', 9);

// Ikon PWA standar (latar putih).
$icon192 = $renderIcon($mark, 192, [255, 255, 255], 0.84);
imagepng($icon192, $iconsDir.'/icon-192.png', 9);
$icon512 = $renderIcon($mark, 512, [255, 255, 255], 0.84);
imagepng($icon512, $iconsDir.'/icon-512.png', 9);

// Ikon maskable (latar putih, konten lebih kecil agar aman di zona aman).
$maskable = $renderIcon($mark, 512, [255, 255, 255], 0.62);
imagepng($maskable, $iconsDir.'/icon-maskable-512.png', 9);

imagedestroy($favicon);
imagedestroy($apple);
imagedestroy($icon192);
imagedestroy($icon512);
imagedestroy($maskable);

/**
 * Bangun favicon.ico berisi PNG 16/32/48 (format ICO mendukung PNG).
 *
 * @param  array<int, string>  $pngPaths
 */
$buildIco = function (array $pngPaths, string $outPath) {
    $entries = [];
    foreach ($pngPaths as $path) {
        $size = getimagesize($path)[0];
        $data = file_get_contents($path);
        $entries[] = ['size' => $size, 'data' => $data];
    }

    $count = count($entries);
    $header = pack('vvv', 0, 1, $count); // reserved, type=1 (icon), count
    $offset = 6 + ($count * 16);
    $directory = '';
    $payload = '';

    foreach ($entries as $e) {
        $size = $e['size'] >= 256 ? 0 : $e['size'];
        $directory .= pack('CCCC', $size, $size, 0, 0)
            .pack('vv', 1, 32)
            .pack('VV', strlen($e['data']), $offset);
        $payload .= $e['data'];
        $offset += strlen($e['data']);
    }

    file_put_contents($outPath, $header.$directory.$payload);
};

$tmp16 = sys_get_temp_dir().'/ln-16.png';
$tmp32 = sys_get_temp_dir().'/ln-32.png';
$tmp48 = sys_get_temp_dir().'/ln-48.png';
imagepng($renderIcon($mark, 16, [0, 0, 0], 0.92, false), $tmp16, 9);
imagepng($renderIcon($mark, 32, [0, 0, 0], 0.92, false), $tmp32, 9);
imagepng($renderIcon($mark, 48, [0, 0, 0], 0.92, false), $tmp48, 9);
$buildIco([$tmp16, $tmp32, $tmp48], $root.'/public/favicon.ico');
@unlink($tmp16);
@unlink($tmp32);
@unlink($tmp48);

imagedestroy($mark);
imagedestroy($img);

echo "Selesai. Aset dibuat:\n";
echo " - public/images/brand/logo.png\n";
echo " - public/images/brand/logo-full.png\n";
echo " - public/images/brand/logo-mark.png\n";
echo " - public/favicon.png & public/favicon.ico\n";
echo " - public/icons/{icon-192,icon-512,icon-maskable-512,apple-touch-icon}.png\n";
