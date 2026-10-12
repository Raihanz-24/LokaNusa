<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi akses publik: hanya beranda yang boleh dibuka tamu.
 *
 * Tamu yang mencoba membuka halaman lain (klik tombol/tautan atau akses
 * langsung URL) dikembalikan ke beranda, lalu beranda menampilkan popup
 * ajakan masuk/daftar. Tujuan awal disimpan agar bisa dilanjutkan setelah login.
 */
class EnsurePublicAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return $next($request);
        }

        // Simpan tujuan agar bisa dilanjutkan setelah login (hanya halaman GET).
        if ($request->isMethod('GET')) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return redirect()
            ->route('home')
            ->with('auth_prompt', true);
    }
}
