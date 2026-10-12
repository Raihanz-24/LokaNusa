<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menetapkan locale aplikasi per permintaan.
 *
 * Sumber, berurutan prioritas:
 * 1. Parameter query `?lang=` (bila didukung) — memungkinkan tautan berbagi bahasa.
 * 2. Sesi `locale` — hasil pilihan tombol switch bahasa.
 * 3. Bawaan aplikasi (config `app.locale`, default `en`).
 *
 * Hanya locale yang didukung (lihat config `app.supported_locales`) yang diterima.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('app.supported_locales', ['en' => 'English']));

        $locale = $request->query('lang');

        if (! is_string($locale) || ! in_array($locale, $supported, true)) {
            $locale = $request->session()->get('locale');
        }

        if (! is_string($locale) || ! in_array($locale, $supported, true)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
