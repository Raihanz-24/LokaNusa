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
     * Apakah kredensial AI sudah terisi.
     */
    public function configured(): bool
    {
        return filled(config('ai.key')) && filled(config('ai.base_url'));
    }

    /**
     * Kirim percakapan dan kembalikan teks balasan, atau null bila gagal.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chat(array $messages, ?User $user = null, string $context = 'trip_planner'): ?string
    {
        if (! $this->configured()) {
            return null;
        }

        try {
            $response = Http::withToken((string) config('ai.key'))
                ->acceptJson()
                ->timeout((int) config('ai.timeout', 60))
                ->post(rtrim((string) config('ai.base_url'), '/').'/chat/completions', [
                    'model' => config('ai.model'),
                    'messages' => $messages,
                    'temperature' => (float) config('ai.temperature', 0.7),
                    'max_tokens' => (int) config('ai.max_tokens', 800),
                ]);
        } catch (Throwable $exception) {
            Log::warning('AI request failed', ['exception' => $exception->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('AI request rejected', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $payload = $response->json();

        $this->recordUsage($user, $context, $payload['usage'] ?? []);

        $content = data_get($payload, 'choices.0.message.content');

        return is_string($content) ? trim($content) : null;
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
