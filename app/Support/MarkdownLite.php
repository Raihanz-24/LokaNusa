<?php

namespace App\Support;

/**
 * Perender Markdown ringan & AMAN untuk balasan AI chat.
 *
 * Alur: seluruh teks di-escape lebih dulu (htmlspecialchars), BARU ditafsirkan
 * Markdown, sehingga tidak ada HTML mentah dari AI/pengguna yang bisa lolos.
 * Hanya tag aman yang dihasilkan: p, br, strong, em, code, ul, ol, li, a.
 *
 * Bukan Markdown lengkap — cukup untuk gaya balasan AI (bold, italic, daftar,
 * heading, tautan, kode inline).
 */
class MarkdownLite
{
    /**
     * Render Markdown menjadi HTML aman.
     */
    public static function render(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        // Escape dulu agar tidak ada tag HTML mentah yang lolos.
        $lines = explode("\n", htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

        $html = [];
        $listType = null;

        foreach ($lines as $line) {
            $line = rtrim($line);
            $isUl = (bool) preg_match('/^\s*[-*+]\s+/', $line);
            $isOl = (bool) preg_match('/^\s*\d+\.\s+/', $line);

            if ($isUl || $isOl) {
                $type = $isUl ? 'ul' : 'ol';

                if ($listType !== $type) {
                    if ($listType !== null) {
                        $html[] = "</{$listType}>";
                    }

                    $html[] = "<{$type}>";
                    $listType = $type;
                }

                $item = (string) preg_replace('/^\s*(?:[-*+]|\d+\.)\s+/', '', $line);
                $html[] = '<li>'.self::inline($item).'</li>';

                continue;
            }

            if ($listType !== null) {
                $html[] = "</{$listType}>";
                $listType = null;
            }

            if (trim($line) === '') {
                continue;
            }

            if (preg_match('/^\s*#{1,6}\s+(.*)$/', $line, $matches)) {
                $html[] = '<p class="font-semibold">'.self::inline($matches[1]).'</p>';

                continue;
            }

            $html[] = '<p>'.self::inline($line).'</p>';
        }

        if ($listType !== null) {
            $html[] = "</{$listType}>";
        }

        return implode('', $html);
    }

    /**
     * Teks biasa → HTML aman dengan baris baru (untuk pesan pengguna).
     */
    public static function plain(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        return nl2br(htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    /**
     * Terapkan gaya inline (bold, italic, code, tautan) pada satu baris.
     */
    private static function inline(string $text): string
    {
        // Kode inline `teks`.
        $text = (string) preg_replace(
            '/`([^`]+)`/',
            '<code>$1</code>',
            $text,
        );

        // Bold **teks** / __teks__.
        $text = (string) preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
        $text = (string) preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $text);

        // Italic *teks* / _teks_.
        $text = (string) preg_replace('/(?<![*\w])\*(?!\s)(.+?)(?<!\s)\*(?![*\w])/s', '<em>$1</em>', $text);
        $text = (string) preg_replace('/(?<![_\w])_(?!\s)(.+?)(?<!\s)_(?![_\w])/s', '<em>$1</em>', $text);

        // Tautan [teks](url) — hanya http/https atau path internal.
        $text = (string) preg_replace_callback(
            '/\[([^\]]+)\]\(((?:https?:\/\/|\/)[^\s)]+)\)/',
            fn (array $m): string => '<a href="'.$m[2].'" target="_blank" rel="noopener noreferrer">'.$m[1].'</a>',
            $text,
        );

        return $text;
    }
}
