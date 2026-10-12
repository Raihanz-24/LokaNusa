<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Mengganti bahasa tampilan (locale) dan menyimpannya di sesi.
 *
 * Mendukung Indonesia (`id`) dan Inggris (`en`). Setelah mengganti,
 * pengguna dikembalikan ke halaman sebelumnya.
 */
class LocaleController extends Controller
{
    public function update(Request $request, string $locale): RedirectResponse
    {
        $supported = array_keys(config('app.supported_locales', ['en' => 'English']));

        if (in_array($locale, $supported, true)) {
            $request->session()->put('locale', $locale);
        }

        return redirect()->back(fallback: route('home'));
    }
}
