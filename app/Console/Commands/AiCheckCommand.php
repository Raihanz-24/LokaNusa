<?php

namespace App\Console\Commands;

use App\Services\Ai\AiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Diagnostik koneksi AI Trip Planner.
 *
 * Menampilkan konfigurasi (key disamarkan), lalu memanggil endpoint
 * `/models` dan `/chat/completions` untuk melihat status & isi respons
 * ASLI dari gateway (mis. Kryptonlab) — tanpa menelan error.
 */
class AiCheckCommand extends Command
{
    protected $signature = 'lokanusa:ai-check
                            {--message=Balas satu kalimat singkat tentang wisata Probolinggo. : Pesan uji}
                            {--max-tokens=0 : Batas token keluaran (0 = ikuti konfigurasi)}';

    protected $description = 'Uji koneksi & kredensial AI (tampilkan status/isi respons asli).';

    public function handle(AiClient $client): int
    {
        $base = (string) config('ai.base_url');
        $key = (string) config('ai.key');
        $model = (string) config('ai.model');
        $maxTokens = (int) $this->option('max-tokens') ?: (int) config('ai.max_tokens', 2000);

        $this->line('Konfigurasi:');
        $this->line('  base_url : '.($base !== '' ? $base : '(KOSONG)'));
        $this->line('  model    : '.($model !== '' ? $model : '(KOSONG)'));
        $this->line('  api_key  : '.($key !== '' ? substr($key, 0, 6).'…('.strlen($key).' char)' : '(KOSONG)'));
        $this->line('  max_tokens: '.$maxTokens);
        $this->line('  retries  : '.config('ai.retries', 3));
        $this->line('  configured(): '.($client->configured() ? 'true' : 'false'));
        $this->newLine();

        if (! $client->configured()) {
            $this->error('Kredensial AI belum lengkap. Set AI_BASE_URL & AI_API_KEY di .env lalu `php artisan config:clear`.');

            return self::FAILURE;
        }

        // 1) Uji endpoint /models (GET).
        $this->line('[1/2] GET '.rtrim($base, '/').'/models');
        try {
            $response = Http::withToken($key)->acceptJson()->timeout(15)
                ->get(rtrim($base, '/').'/models');

            $this->line('  Status : '.$response->status());
            $this->line('  Body   : '.mb_substr($response->body(), 0, 500));
        } catch (Throwable $exception) {
            $this->error('  Exception: '.$exception->getMessage());
        }
        $this->newLine();

        // 2) Uji endpoint /chat/completions (POST) — jalur yang dipakai aplikasi.
        $this->line('[2/2] POST '.rtrim($base, '/').'/chat/completions');
        $message = (string) $this->option('message');

        try {
            $response = Http::withToken($key)->acceptJson()->timeout(30)
                ->post(rtrim($base, '/').'/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => $message],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => $maxTokens,
                ]);

            $this->line('  Status : '.$response->status());

            $payload = $response->json();
            $content = data_get($payload, 'choices.0.message.content');
            $reasoning = data_get($payload, 'choices.0.message.reasoning_content');

            $this->line('  finish_reason: '.var_export(data_get($payload, 'choices.0.finish_reason'), true));
            $this->line('  usage: '.json_encode($payload['usage'] ?? [], JSON_UNESCAPED_UNICODE));
            $this->line('  content_chars: '.(is_string($content) ? mb_strlen($content) : 0));
            $this->line('  reasoning_chars: '.(is_string($reasoning) ? mb_strlen($reasoning) : 0));
            $this->line('  Body   : '.mb_substr($response->body(), 0, 800));

            if ($response->successful()) {
                $text = trim((string) $content);

                if ($text === '') {
                    $this->newLine();
                    $this->error('Respons sukses tetapi TIDAK ADA teks jawaban.');

                    if (is_string($reasoning) && $reasoning !== '') {
                        $this->warn('Model menghabiskan token untuk "reasoning_content" sebelum menulis jawaban.');
                        $this->line('Naikkan batas token, misalnya:');
                        $this->line('  php artisan lokanusa:ai-check --max-tokens=3000');
                        $this->line('lalu set AI_MAX_TOKENS=2000–4000 di .env.');
                    }

                    return self::FAILURE;
                }

                $this->newLine();
                $this->info('Balasan: '.$text);

                return self::SUCCESS;
            }

            $this->newLine();
            $this->error('AI menolak permintaan. Periksa pesan di atas (kunci salah? model salah? kuota habis?).');

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error('  Exception: '.$exception->getMessage());
            $this->newLine();
            $this->error('Tidak bisa menghubungi gateway. Kemungkinan koneksi keluar server diblokir (firewall) atau base_url salah.');

            return self::FAILURE;
        }
    }
}
