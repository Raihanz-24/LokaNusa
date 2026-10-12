<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Concerns\RedirectsAfterAuth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    use RedirectsAfterAuth;

    /**
     * Tampilkan halaman masuk.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Proses permintaan autentikasi (standar Breeze: rate-limited).
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return $this->redirectAfterAuth($request);
    }

    /**
     * Keluarkan sesi pengguna.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
