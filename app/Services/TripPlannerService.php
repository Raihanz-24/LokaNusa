<?php

namespace App\Services;

use App\Models\AiKnowledgeEntry;
use App\Models\AppSetting;
use App\Models\Itinerary;
use App\Models\TripPlannerMessage;
use App\Models\TripPlannerUsage;
use App\Models\User;
use App\Services\Ai\AiClient;
use App\Services\Ai\ItineraryPlanner;
use App\Support\MarkdownLite;
use App\Support\RegionResolver;
use Illuminate\Support\Facades\DB;

/**
 * AI Trip Planner.
 *
 * Percakapan disimpan per pengguna di database (tidak hilang saat berpindah
 * halaman). Balasan asisten diambil dari layanan AI nyata melalui
 * {@see AiClient}; bila AI belum dikonfigurasi atau gagal, dipakai balasan
 * cadangan deterministik (mesin aturan) agar UI tetap berfungsi.
 *
 * Pembatasan (kuota prompt harian, batas jumlah pesan, batas token per
 * pengguna) bersifat OPTIONAL dan diatur admin. Secara default TIDAK ada
 * pembatasan.
 */
class TripPlannerService
{
    /**
     * Konteks yang disertakan ke AI: hanya beberapa pesan terakhir agar hemat
     * token.
     */
    private const AI_CONTEXT_MESSAGES = 10;

    public function __construct(
        private readonly AiClient $ai,
        private readonly ItineraryPlanner $planner,
        private readonly ItineraryService $itineraryService,
        private readonly ItineraryPlanWriter $planWriter,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Ambil seluruh pesan percakapan milik pengguna (paling lama -> terbaru).
     *
     * `html` = versi aman untuk dirender (balasan asisten memakai gaya ringan,
     * pesan pengguna sebagai teks biasa). `text` tetap disertakan apa adanya.
     *
     * @return array<int, array{id: int, role: string, text: string, html: string, time: string, has_plan: bool, plan_summary: ?string}>
     */
    public function messages(User $user): array
    {
        return $user->tripPlannerMessages()
            ->orderBy('id')
            ->get()
            ->map(fn (TripPlannerMessage $message): array => [
                'id' => $message->getKey(),
                'role' => $message->role,
                'text' => $message->content,
                'html' => $message->role === 'user'
                    ? MarkdownLite::plain($message->content)
                    : MarkdownLite::render($message->content),
                'time' => $message->created_at?->format('H:i') ?? now()->format('H:i'),
                'has_plan' => filled($message->plan),
                'plan_summary' => filled($message->plan)
                    ? $this->planner->summarize($message->plan)
                    : null,
            ])
            ->all();
    }

    /**
     * Balasan pembuka dari asisten saat percakapan masih kosong.
     */
    public function welcomeMessage(): string
    {
        return __('ui.trip_planner_welcome');
    }

    /**
     * Batas prompt harian dari pengaturan admin (null = tanpa batas).
     */
    public function dailyPromptLimit(): ?int
    {
        $value = AppSetting::get('ai_daily_prompt_limit');

        return filled($value) ? (int) $value : null;
    }

    /**
     * Sisa kuota prompt pengguna hari ini (null = tanpa batas).
     */
    public function remainingPrompts(User $user): ?int
    {
        $limit = $this->dailyPromptLimit();

        if ($limit === null) {
            return null;
        }

        $used = $this->usageToday($user)->prompts;

        return max(0, $limit - $used);
    }

    /**
     * Apakah pengguna masih boleh mengirim prompt (prompt harian & token AI).
     */
    public function canSend(User $user): bool
    {
        $remaining = $this->remainingPrompts($user);

        if ($remaining !== null && $remaining <= 0) {
            return false;
        }

        return $this->ai->withinLimit($user);
    }

    /**
     * Kirim pesan pengguna dan kembalikan seluruh percakapan terbarui.
     *
     * @return array<int, array{role: string, text: string, time: string}>
     *
     * @throws \RuntimeException Bila batas prompt harian atau token AI tercapai.
     */
    public function send(User $user, string $message): array
    {
        if (! $this->canSend($user)) {
            throw new \RuntimeException($this->ai->withinLimit($user) ? 'quota' : 'token_limit');
        }

        DB::transaction(function () use ($user, $message): void {
            // Bila percakapan masih kosong, awali dengan sapaan asisten.
            if ($user->tripPlannerMessages()->doesntExist()) {
                $this->store($user, 'assistant', $this->welcomeMessage());
            }

            $history = $this->aiHistory($user);

            $this->store($user, 'user', $message);

            $reply = $this->replyWithPlan($user, $message, $history);

            // Balasan itinerary menyertakan rencana terstruktur (untuk disimpan).
            if ($reply['plan'] !== null) {
                $this->store($user, 'assistant', $this->planner->toChatMarkdown($reply['plan']), $reply['plan']);

                // Beri tahu pengguna bahwa rencana siap disimpan ke Rencana.
                $this->notifications->itineraryReady($user, $reply['plan']['title'] ?? null);
            } else {
                $this->store($user, 'assistant', $reply['text']);
            }

            $this->incrementUsage($user);
            $this->prune($user);
        });

        return $this->messages($user);
    }

    /**
     * Kosongkan percakapan pengguna (tidak mengurangi kuota).
     */
    public function clear(User $user): void
    {
        $user->tripPlannerMessages()->delete();
    }

    /**
     * Susun balasan asisten untuk sebuah pesan pengguna.
     *
     * Urutan: coba AI nyata dulu; bila belum dikonfigurasi atau gagal,
     * gunakan balasan cadangan deterministik.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     */
    public function reply(User $user, string $message, array $history = []): string
    {
        return $this->replyWithPlan($user, $message, $history)['text'];
    }

    /**
     * Susun balasan asisten; bila permintaannya berupa itinerary, kembalikan
     * pula rencana terstruktur (`plan`) karena AI diminta membalas JSON.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array{text: string, plan: ?array}
     */
    public function replyWithPlan(User $user, string $message, array $history = []): array
    {
        $text = trim($message);

        if ($text === '') {
            return ['text' => __('ui.trip_planner_empty'), 'plan' => null];
        }

        // 1) Cek basis pengetahuan admin (kata kunci -> jawaban) lebih dulu.
        $knowledge = $this->knowledgeAnswer($text);

        if ($knowledge !== null) {
            return ['text' => $knowledge, 'plan' => null];
        }

        $wantsPlan = $this->planner->wantsItinerary($text);

        $system = $this->systemPrompt();

        if ($wantsPlan) {
            $system .= "\n\n".$this->planner->jsonInstructions();
        }

        $answer = $this->ai->chat(
            array_merge(
                [['role' => 'system', 'content' => $system]],
                $history,
                [['role' => 'user', 'content' => $text]],
            ),
            $user,
            'trip_planner',
            $wantsPlan ? 1600 : null,
        );

        // AI tidak tersedia -> balasan cadangan deterministik.
        if ($answer === null) {
            return ['text' => $this->fallbackReply($text), 'plan' => null];
        }

        // Untuk permintaan itinerary, coba tafsirkan balasan sebagai JSON.
        if ($wantsPlan) {
            $plan = $this->planner->parse($answer);

            if ($plan !== null && ! empty($plan['days'])) {
                return ['text' => $this->planner->toChatMarkdown($plan), 'plan' => $plan];
            }
        }

        return ['text' => $answer, 'plan' => null];
    }

    /**
     * Terapkan rencana yang tersimpan pada sebuah pesan asisten ke Rencana
     * perjalanan (itinerary) milik pengguna. Bila `itinerary_id` kosong, buat
     * rencana baru berjudul sesuai plan.
     *
     * @return array{itinerary: Itinerary, added: int}
     */
    public function applyPlan(User $user, TripPlannerMessage $message, ?int $itineraryId = null): array
    {
        abort_unless($message->user_id === $user->getKey(), 403);

        $plan = $message->plan;

        abort_if(blank($plan), 422, 'Pesan ini tidak memuat rencana.');

        if ($itineraryId !== null) {
            $itinerary = $user->itineraries()->whereKey($itineraryId)->firstOrFail();
        } else {
            $title = filled($plan['title'] ?? null)
                ? (string) $plan['title']
                : __('ui.trip_planner_plan_default_title', ['region' => $this->planner->regionName()]);

            $itinerary = $this->itineraryService->create($user, $title);
        }

        $added = $this->planWriter->write($user, $itinerary, $plan);

        return ['itinerary' => $itinerary, 'added' => $added];
    }

    /**
     * Jawaban dari basis pengetahuan admin, bila ada yang cocok.
     */
    public function knowledgeAnswer(string $message): ?string
    {
        $text = mb_strtolower($message);

        $entry = AiKnowledgeEntry::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->first(fn (AiKnowledgeEntry $entry): bool => $entry->matches($text));

        return $entry?->answer;
    }

    /**
     * Instruksi sistem untuk AI (konteks wilayah fokus & gaya bahasa).
     * Dapat diubah admin lewat Pengaturan (`ai_system_prompt`), mendukung
     * placeholder `:region`.
     */
    public function systemPrompt(): string
    {
        $custom = AppSetting::get('ai_system_prompt');

        if (filled($custom)) {
            return str_replace(':region', $this->regionName(), (string) $custom);
        }

        return __('ui.trip_planner_system_prompt', ['region' => $this->regionName()]);
    }

    /**
     * Balasan cadangan (mesin aturan) bila AI tidak tersedia.
     */
    private function fallbackReply(string $message): string
    {
        $text = mb_strtolower($message);

        if ($this->matches($text, ['halo', 'hai', 'hi', 'hello', 'pagi', 'siang', 'malam'])) {
            return __('ui.trip_planner_greeting', ['region' => $this->regionName()]);
        }

        if ($this->matches($text, ['hari', 'malam', 'durasi', 'berapa lama'])) {
            return __('ui.trip_planner_ask_days');
        }

        if ($this->matches($text, ['kuliner', 'makan', 'restoran', 'umkm', 'oleh-oleh'])) {
            return __('ui.trip_planner_categories', ['list' => $this->categoryList()]);
        }

        if ($this->matches($text, ['budaya', 'tradisi', 'sejarah', 'seni'])) {
            return __('ui.trip_planner_categories', ['list' => $this->categoryList()]);
        }

        if ($this->matches($text, ['budget', 'anggaran', 'biaya', 'murah', 'hemat'])) {
            return __('ui.trip_planner_budget');
        }

        if ($this->matches($text, ['peta', 'lokasi', 'jarak', 'arah', 'map'])) {
            return __('ui.trip_planner_map');
        }

        if ($this->matches($text, ['terima kasih', 'makasih', 'thanks', 'thank you'])) {
            return __('ui.trip_planner_thanks');
        }

        return __('ui.trip_planner_fallback', ['list' => $this->categoryList()]);
    }

    /**
     * Riwayat percakapan terakhir untuk konteks AI (tanpa sapaan pembuka).
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function aiHistory(User $user): array
    {
        return $user->tripPlannerMessages()
            ->orderByDesc('id')
            ->limit(self::AI_CONTEXT_MESSAGES)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (TripPlannerMessage $message): array => [
                'role' => $message->role === 'user' ? 'user' : 'assistant',
                'content' => $message->content,
            ])
            ->all();
    }

    /**
     * Simpan satu pesan.
     */
    private function store(User $user, string $role, string $content, ?array $plan = null): void
    {
        $user->tripPlannerMessages()->create([
            'role' => $role,
            'content' => $content,
            'plan' => $plan,
        ]);
    }

    /**
     * Tambah hitungan prompt hari ini.
     */
    private function incrementUsage(User $user): void
    {
        $usage = $this->usageToday($user);
        $usage->increment('prompts');
    }

    /**
     * Baris kuota hari ini (dibuat bila belum ada).
     */
    private function usageToday(User $user): TripPlannerUsage
    {
        return TripPlannerUsage::query()->firstOrCreate(
            ['user_id' => $user->getKey(), 'date' => now()->toDateString()],
            ['prompts' => 0],
        );
    }

    /**
     * Pangkas riwayat bila admin mengatur batas jumlah pesan (null = tanpa
     * batas; pesan paling lama dihapus lebih dulu).
     */
    private function prune(User $user): void
    {
        $max = AppSetting::get('ai_max_messages');

        if (blank($max)) {
            return;
        }

        $total = $user->tripPlannerMessages()->count();

        $excess = $total - (int) $max;

        if ($excess <= 0) {
            return;
        }

        $oldestIds = $user->tripPlannerMessages()
            ->orderBy('id')
            ->limit($excess)
            ->pluck('id');

        TripPlannerMessage::query()->whereIn('id', $oldestIds)->delete();
    }

    /**
     * Apakah pesan memuat salah satu kata kunci.
     *
     * @param  array<int, string>  $keywords
     */
    private function matches(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Nama wilayah fokus, untuk konteks balasan.
     */
    private function regionName(): string
    {
        $region = RegionResolver::focus();

        return $region?->name ?? __('ui.trip_planner_default_region');
    }

    /**
     * Daftar minat contoh yang bisa dipilih pengguna.
     */
    private function categoryList(): string
    {
        return collect([
            __('ui.trip_planner_cat_nature'),
            __('ui.trip_planner_cat_culinary'),
            __('ui.trip_planner_cat_culture'),
            __('ui.trip_planner_cat_stay'),
        ])->implode(', ');
    }
}
