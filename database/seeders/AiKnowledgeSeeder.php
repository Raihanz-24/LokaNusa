<?php

namespace Database\Seeders;

use App\Models\AiKnowledgeEntry;
use Illuminate\Database\Seeder;

/**
 * Contoh basis pengetahuan AI Trip Planner.
 *
 * Hanya dijalankan lewat `db:seed --class=AiKnowledgeSeeder` agar entri
 * default tidak menimpa data yang diubah admin saat `migrate:fresh --seed`.
 */
class AiKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        $entries = [
            [
                'title' => 'Tentang developer / pengembang aplikasi',
                'keywords' => 'siapa developer, siapa yang membuat, siapa pembuat, pengembang aplikasi, developer aplikasi, who made, who developed, who is the developer',
                'answer' => 'Aplikasi LokaNusa dikembangkan oleh M. Raihan Ramadhan, seorang web developer yang memiliki usaha di bidang software bernama kafeinxcode.com.',
                'sort_order' => 1,
            ],
            [
                'title' => 'Tentang aplikasi LokaNusa',
                'keywords' => 'apa itu lokanusa, tentang aplikasi, aplikasi ini apa, what is lokanusa',
                'answer' => 'LokaNusa adalah aplikasi pariwisata cerdas yang membantu Anda menjelajah destinasi, kuliner, budaya, dan penginapan di satu kabupaten/kota, lengkap dengan perencana perjalanan bertenaga AI.',
                'sort_order' => 2,
            ],
        ];

        foreach ($entries as $entry) {
            AiKnowledgeEntry::query()->updateOrCreate(
                ['title' => $entry['title']],
                $entry + ['is_active' => true],
            );
        }
    }
}
