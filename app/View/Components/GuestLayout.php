<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    /**
     * Buat instance komponen baru.
     */
    public function __construct(public ?string $title = null) {}

    /**
     * Tampilan layout untuk halaman tamu (auth).
     */
    public function render(): View
    {
        return view('layouts.guest');
    }
}
