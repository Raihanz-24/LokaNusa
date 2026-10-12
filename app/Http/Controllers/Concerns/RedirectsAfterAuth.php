<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Menentukan tujuan setelah login/daftar.
 *
 * Prioritas: parameter `redirect` (hanya path internal yang aman) → tujuan
 * tersimpan (session `url.intended`) → beranda.
 */
trait RedirectsAfterAuth
{
    protected function redirectAfterAuth(Request $request): RedirectResponse
    {
        $target = $request->input('redirect');

        if (is_string($target) && $this->isSafeRedirect($target)) {
            return redirect()->to($target);
        }

        return redirect()->intended(route('home', absolute: false));
    }

    /**
     * Hanya izinkan path internal (relatif) agar tidak terjadi open-redirect.
     */
    protected function isSafeRedirect(string $target): bool
    {
        if (! str_starts_with($target, '/') || str_starts_with($target, '//')) {
            return false;
        }

        $parts = parse_url($target);

        if ($parts === false || ! empty($parts['host'])) {
            return false;
        }

        return true;
    }
}
