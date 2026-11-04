<?php

namespace App\Services\Ai;

use App\Models\AiUsage;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Klien AI generik (OpenAI-compatible "chat completions").
 *
 * Dipakai gateway seperti Kryptonlab. Kredensial & model dibaca dari
 * {@see config('ai')} sehingga cukup ganti lewat .env.
 *
 * Setiap panggilan yang berhasil mencatat pemakaian token ke tabel `ai_usages`
 * sehingga admin dapat memantau total token seluruh pengguna.
 */
class AiClient
{
    /**
     * Status HTTP yang layak dicoba ulang (gangguan sementara penyedia).
     */
    private const RETRYABLE_STATUSES = [408, 409, 425, 429, 500, 502, 503, 504];

    /**
     * Apakah kredensial AI sudah terisi.
     */
    public function configured(): bool
    {
        return filled(config('ai.key')) && filled(config('ai.base_url'));
    }

    /**
     * Kirim percakapan dan kembalikan teks balasan, atau null bila gagal.
     *
     * Permintaan diulang beberapa kali untuk gangguan sementara (timeout,
     * koneksi putus, 429, 5xx) agar satu kegagalan jaringan tidak langsung
     * memunculkan balasan template.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chat(array $messages, ?User $user = null, string $context = 'trip_planner', ?int $maxTokens = null, ?float $temperature = null): ?string
    {
        if (! $this->configured()) {
            return null;
        }

        $attempts = min(5, max(1, (int) config('ai.retries', 3)));
        $delayMs = min(5000, max(0, (int) config('ai.retry_delay_ms', 400)));

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = Http::withToken((string) config('ai.key'))
                    ->acceptJson()
                    ->timeout((int) config('ai.timeout', 60))
                    ->post(rtrim((string) config('ai.base_url'), '/').'/chat/completions', [
                        'model' => config('ai.model'),
                        'messages' => $messages,
                        'temperature' => $temperature ?? (float) config('ai.temperature', 0.7),
                        'max_tokens' => $maxTokens ?? (int) config('ai.max_tokens', 800),
                    ]);
            } catch (Throwable $exception) {
                Log::warning('AI request failed', [
                    'attempt' => $attempt,
                    'attempts' => $attempts,
                    'exception' => $exception->getMessage(),
                ]);

                if ($attempt < $attempts) {
                    $this->backoff($attempt, $delayMs);

                    continue;
                }

                return null;
            }

            if (! $response->successful()) {
                Log::warning('AI request rejected', [
                    'attempt' => $attempt,
                    'attempts' => $attempts,
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);

                if ($attempt < $attempts && in_array($response->status(), self::RETRYABLE_STATUSES, true)) {
                    $this->backoff($attempt, $delayMs);

                    continue;
                }

                return null;
            }

            $payload = $response->json();
            $content = data_get($payload, 'choices.0.message.content');

            // Sebagian gateway mengirim content sebagai array bagian teks.
            if (is_array($content)) {
                $content = collect($content)
                    ->map(fn ($part) => is_array($part) ? (string) ($part['text'] ?? '') : (string) $part)
                    ->implode('');
            }

            if (! is_string($content) || trim($content) === '') {
                Log::warning('AI response has no usable content', [
                    'attempt' => $attempt,
                    'attempts' => $attempts,
                    'finish_reason' => data_get($payload, 'choices.0.finish_reason'),
                    'keys' => is_array($payload) ? array_keys($payload) : [],
                ]);

                if ($attempt < $attempts) {
                    $this->backoff($attempt, $delayMs);

                    continue;
                }

                return null;
            }

            $this->recordUsage($user, $context, $payload['usage'] ?? []);

            return trim($content);
        }

        return null;
    }

    /**
     * Jeda sebelum percobaan berikutnya (bertambah perlahan).
     */
    private function backoff(int $attempt, int $baseMs): void
    {
        if ($baseMs <= 0) {
            return;
        }

        usleep($baseMs * (2 ** ($attempt - 1)) * 1000);
    }

    /**
     * Total token yang dipakai pengguna hari ini.
     */
    public function tokensUsedToday(User $user): int
    {
        return (int) AiUsage::query()
            ->where('user_id', $user->getKey())
            ->where('created_at', '>=', Carbon::today())
            ->sum('total_tokens');
    }

    /**
     * Apakah pengguna masih boleh memakai AI (berdasarkan batas token harian).
     * Pengguna tanpa batas (null) selalu boleh.
     */
    public function withinLimit(User $user): bool
    {
        $limit = $user->ai_token_limit;

        if ($limit === null) {
            return true;
        }

        return $this->tokensUsedToday($user) < $limit;
    }

    /**
     * Catat pemakaian token dari sebuah respons.
     *
     * @param  array<string, mixed>  $usage
     */
    private function recordUsage(?User $user, string $context, array $usage): void
    {
        $prompt = (int) ($usage['prompt_tokens'] ?? 0);
        $completion = (int) ($usage['completion_tokens'] ?? 0);
        $total = (int) ($usage['total_tokens'] ?? ($prompt + $completion));

        if ($total === 0 && $prompt === 0 && $completion === 0) {
            return;
        }

        AiUsage::query()->create([
            'user_id' => $user?->getKey(),
            'context' => $context,
            'model' => config('ai.model'),
            'prompt_tokens' => $prompt,
            'completion_tokens' => $completion,
            'total_tokens' => $total,
        ]);
    }
}
