<?php

namespace Tests\Unit;

use App\Support\MarkdownLite;
use PHPUnit\Framework\TestCase;

class MarkdownLiteTest extends TestCase
{
    public function test_menghilangkan_bintang_menjadi_bold(): void
    {
        $html = MarkdownLite::render('Ini **penting** sekali.');

        $this->assertStringContainsString('<strong>penting</strong>', $html);
        $this->assertStringNotContainsString('**', $html);
    }

    public function test_daftar_berpoin_menjadi_ul_li(): void
    {
        $html = MarkdownLite::render("- Destinasi\n- Kuliner\n- Budaya");

        $this->assertStringContainsString('<ul>', $html);
        $this->assertSame(3, substr_count($html, '<li>'));
        $this->assertStringContainsString('Destinasi</li>', $html);
    }

    public function test_daftar_bernomor_menjadi_ol_li(): void
    {
        $html = MarkdownLite::render("1. Pertama\n2. Kedua");

        $this->assertStringContainsString('<ol>', $html);
        $this->assertSame(2, substr_count($html, '<li>'));
    }

    public function test_mengescape_html_mentah(): void
    {
        $html = MarkdownLite::render('<script>alert(1)</script> dan <b>tebal</b>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<b>tebal</b>', $html);
    }

    public function test_tautan_dirender_aman(): void
    {
        $html = MarkdownLite::render('Lihat [peta](/peta) atau [situs](https://example.com).');

        $this->assertStringContainsString('<a href="/peta"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
    }

    public function test_plain_mempertahankan_baris_baru_tanpa_markdown(): void
    {
        $html = MarkdownLite::plain("Baris satu\nBaris **dua**");

        $this->assertStringContainsString('<br', $html);
        $this->assertStringContainsString('**dua**', $html);
    }
}
