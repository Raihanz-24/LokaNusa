<?php

namespace App\Http\Middleware;

use App\Support\Features;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memblokir rute yang fiturnya sedang dinonaktifkan (feature flag) dengan 404.
 *
 * Dipakai untuk menyembunyikan modul sementara tanpa menghapus rutenya,
 * sehingga tautan/`route()` tetap valid namun tidak dapat diakses.
 */
class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $enabled = match ($feature) {
            'booking' => Features::booking(),
            default => false,
        };

        abort_unless($enabled, 404);

        return $next($request);
    }
}
