<?php

// Utilitas kecil: memastikan berkas terjemahan UI (EN & ID) seimbang
// (jumlah kunci sama, tidak ada nilai kosong). Jalankan: php scripts/check_translations.php

$en = require __DIR__.'/../lang/en/ui.php';
$id = require __DIR__.'/../lang/id/ui.php';

/**
 * Ratakan array bersarang menjadi daftar path => nilai (dipisah titik).
 *
 * @param  array<string, mixed>  $arr
 * @return array<string, mixed>
 */
function flatten(array $arr, string $prefix = ''): array
{
    $out = [];

    foreach ($arr as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

        if (is_array($value)) {
            $out = array_merge($out, flatten($value, $path));
        } else {
            $out[$path] = $value;
        }
    }

    return $out;
}

$enFlat = flatten($en);
$idFlat = flatten($id);

$onlyEn = array_diff_key($enFlat, $idFlat);
$onlyId = array_diff_key($idFlat, $enFlat);

echo 'Jumlah kunci EN: '.count($enFlat).PHP_EOL;
echo 'Jumlah kunci ID: '.count($idFlat).PHP_EOL;
echo 'Hanya di EN: '.(empty($onlyEn) ? '-' : implode(', ', array_keys($onlyEn))).PHP_EOL;
echo 'Hanya di ID: '.(empty($onlyId) ? '-' : implode(', ', array_keys($onlyId))).PHP_EOL;

$empty = 0;
foreach (['en' => $enFlat, 'id' => $idFlat] as $locale => $flat) {
    foreach ($flat as $path => $value) {
        if ($value === null || trim((string) $value) === '') {
            echo "Nilai kosong: {$locale}.{$path}".PHP_EOL;
            $empty++;
        }
    }
}

echo $empty === 0 ? 'Tidak ada nilai kosong.'.PHP_EOL : "Total nilai kosong: {$empty}".PHP_EOL;
