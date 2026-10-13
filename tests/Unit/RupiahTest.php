<?php

namespace Tests\Unit;

use App\Support\Rupiah;
use PHPUnit\Framework\TestCase;

class RupiahTest extends TestCase
{
    public function test_memformat_angka_rupiah(): void
    {
        $this->assertSame('Rp1.500.000', Rupiah::format(1500000));
        $this->assertSame('Rp0', Rupiah::format(0));
        $this->assertNull(Rupiah::format(null));
    }

    public function test_label_gratis_dan_belum_diestimasi(): void
    {
        $this->assertSame('Rp25.000', Rupiah::label(25000));
    }
}
