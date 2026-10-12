<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Basis pengetahuan AI Trip Planner.
 *
 * Bila pesan pengguna memuat salah satu `keywords`, jawaban `answer`
 * dikembalikan langsung tanpa memanggil AI (hemat token & hasil pasti).
 */
class AiKnowledgeEntry extends Model
{
    protected $fillable = [
        'title',
        'keywords',
        'answer',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Daftar kata kunci (sudah dirapikan, huruf kecil).
     *
     * @return array<int, string>
     */
    public function keywordList(): array
    {
        return collect(explode(',', (string) $this->keywords))
            ->map(fn (string $keyword): string => mb_strtolower(trim($keyword)))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Apakah teks memuat salah satu kata kunci entri ini.
     */
    public function matches(string $text): bool
    {
        foreach ($this->keywordList() as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
