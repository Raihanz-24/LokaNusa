<?php

namespace App\Services;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Accommodation;
use App\Models\AppNotification;
use App\Models\Booking;
use App\Models\CultureArticle;
use App\Models\Destination;
use App\Models\Event;
use App\Models\UmkmBusiness;
use App\Models\User;
use App\Support\Rupiah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Layanan notifikasi dalam aplikasi (in-app) LokaNusa.
 *
 * Semua notifikasi disimpan pada tabel `app_notifications` dan ditampilkan
 * lewat lonceng header + halaman /notifikasi. Tidak ada kiriman ke pihak
 * ketiga (tanpa biaya) dan tidak ada notifikasi palsu.
 */
class NotificationService
{
    /**
     * Kirim satu notifikasi ke seorang pengguna.
     */
    public function notify(
        User $user,
        string $type,
        string $title,
        ?string $body = null,
        ?string $url = null,
        ?string $icon = null,
    ): AppNotification {
        return AppNotification::query()->create([
            'user_id' => $user->getKey(),
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'icon' => $icon ?? $this->iconFor($type),
        ]);
    }

    /**
     * Kirim notifikasi yang sama ke banyak pengguna sekaligus (hemat query).
     *
     * @param  iterable<int, User>  $users
     */
    public function notifyMany(
        iterable $users,
        string $type,
        string $title,
        ?string $body = null,
        ?string $url = null,
        ?string $icon = null,
    ): int {
        $icon ??= $this->iconFor($type);
        $now = now();
        $rows = [];

        foreach ($users as $user) {
            $rows[] = [
                'user_id' => $user->getKey(),
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'url' => $url,
                'icon' => $icon,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            AppNotification::query()->insert($chunk);
        }

        return count($rows);
    }

    /**
     * Kirim notifikasi ke seluruh moderator (peran dengan permission
     * moderasi konten), kecuali pengguna tertentu (mis. pelaku).
     *
     * @param  array<int, int>  $exceptIds
     */
    public function notifyModerators(
        string $title,
        ?string $body = null,
        ?string $url = null,
        string $type = AppNotification::TYPE_MODERATION,
        array $exceptIds = [],
    ): int {
        $roles = collect(UserRole::cases())
            ->filter(fn (UserRole $role): bool => $role->can(Permission::ModerateContent))
            ->map(fn (UserRole $role): string => $role->value)
            ->all();

        $query = User::query()->whereIn('role', $roles);

        if ($exceptIds !== []) {
            $query->whereNotIn('id', $exceptIds);
        }

        return $this->notifyMany(
            $query->cursor(),
            $type,
            $title,
            $body,
            $url,
        );
    }

    /**
     * Notifikasi ke semua pengguna lain (opsional kecuali daftar id).
     *
     * @param  array<int, int>  $exceptIds
     */
    public function notifyEveryone(
        string $type,
        string $title,
        ?string $body = null,
        ?string $url = null,
        ?string $icon = null,
        array $exceptIds = [],
    ): int {
        $query = User::query();

        if ($exceptIds !== []) {
            $query->whereNotIn('id', $exceptIds);
        }

        return $this->notifyMany($query->cursor(), $type, $title, $body, $url, $icon);
    }

    /*
    |--------------------------------------------------------------------------
    | Pemicu (trigger) berbasis konten
    |--------------------------------------------------------------------------
    */

    /**
     * Konten diajukan untuk review -> beri tahu moderator.
     */
    public function contentSubmitted(Model $content, ?User $actor = null): void
    {
        $this->notifyModerators(
            __('ui.notif_content_submitted_title'),
            __('ui.notif_content_submitted_body', [
                'title' => $this->contentTitle($content),
                'name' => $actor?->name ?? __('ui.notif_someone'),
            ]),
            $this->moderationUrl(),
            AppNotification::TYPE_MODERATION,
            array_filter([$actor?->getKey()]),
        );
    }

    /**
     * Konten disetujui -> beri tahu pemiliknya.
     */
    public function contentApproved(Model $content): void
    {
        $owner = $this->ownerOf($content);

        if ($owner === null) {
            return;
        }

        $this->notify(
            $owner,
            AppNotification::TYPE_MODERATION,
            __('ui.notif_content_approved_title'),
            __('ui.notif_content_approved_body', ['title' => $this->contentTitle($content)]),
            $this->contentUrl($content),
        );
    }

    /**
     * Konten ditolak -> beri tahu pemiliknya beserta alasan.
     */
    public function contentRejected(Model $content, string $reason): void
    {
        $owner = $this->ownerOf($content);

        if ($owner === null) {
            return;
        }

        $this->notify(
            $owner,
            AppNotification::TYPE_MODERATION,
            __('ui.notif_content_rejected_title'),
            __('ui.notif_content_rejected_body', [
                'title' => $this->contentTitle($content),
                'reason' => $reason,
            ]),
            $this->contentUrl($content),
        );
    }

    /**
     * Event terbit -> beri tahu seluruh pengguna (kecuali pengelola event).
     */
    public function eventPublished(Event $event): void
    {
        $this->notifyEveryone(
            AppNotification::TYPE_EVENT,
            __('ui.notif_event_published_title'),
            __('ui.notif_event_published_body', ['title' => $event->title]),
            route('events.show', $event->slug),
            'calendar-days',
            array_filter([$event->organizer_id]),
        );
    }

    /**
     * Konten ditandai "Rekomendasi" admin -> beri tahu pemilik/pengelola.
     */
    public function contentHighlighted(Model $content): void
    {
        $owner = $this->ownerOf($content);

        if ($owner === null) {
            return;
        }

        $this->notify(
            $owner,
            AppNotification::TYPE_HIGHLIGHT,
            __('ui.notif_highlight_title'),
            __('ui.notif_highlight_body', ['title' => $this->contentTitle($content)]),
            $this->contentUrl($content),
            'sparkles',
        );
    }

    /**
     * Rencana itinerary dari balasan AI siap disimpan -> beri tahu pengguna.
     */
    public function itineraryReady(User $user, ?string $title = null): void
    {
        $this->notify(
            $user,
            AppNotification::TYPE_AI,
            __('ui.notif_ai_plan_title'),
            filled($title)
                ? __('ui.notif_ai_plan_body_named', ['title' => $title])
                : __('ui.notif_ai_plan_body'),
            route('trip-planner.index'),
            'sparkles',
        );
    }

    /**
     * Pesanan DEMO baru dibuat -> beri tahu pemilik usaha (penginapan/UMKM).
     */
    public function bookingCreated(Booking $booking): void
    {
        $owner = $booking->owner;

        if ($owner === null) {
            return;
        }

        $this->notify(
            $owner,
            AppNotification::TYPE_BOOKING,
            __('ui.notif_booking_new_title'),
            __('ui.notif_booking_new_body', [
                'item' => $this->bookingItemName($booking),
                'buyer' => $booking->contact_name,
            ]),
            route('bookings.show', $booking->code),
            'shopping-bag',
        );
    }

    /**
     * Pembayaran DEMO selesai -> beri tahu pemilik usaha.
     */
    public function bookingPaid(Booking $booking): void
    {
        $owner = $booking->owner;

        if ($owner === null) {
            return;
        }

        $this->notify(
            $owner,
            AppNotification::TYPE_BOOKING,
            __('ui.notif_booking_paid_title'),
            __('ui.notif_booking_paid_body', [
                'item' => $this->bookingItemName($booking),
                'total' => Rupiah::label($booking->total_price),
            ]),
            route('bookings.show', $booking->code),
            'shopping-bag',
        );
    }

    private function bookingItemName(Booking $booking): string
    {
        return (string) ($booking->bookable?->name ?? __('ui.booking_item'));
    }

    /*
    |--------------------------------------------------------------------------
    | Baca / kelola notifikasi
    |--------------------------------------------------------------------------
    */

    public function unreadCount(User $user): int
    {
        return $this->forUser($user)->unread()->count();
    }

    /**
     * Notifikasi terbaru untuk dropdown lonceng.
     *
     * @return Collection<int, AppNotification>
     */
    public function recent(User $user, int $limit = 8): Collection
    {
        return $this->forUser($user)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Daftar notifikasi berpaginasi untuk halaman /notifikasi.
     */
    public function paginate(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $this->forUser($user)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function markRead(User $user, int $id): bool
    {
        $notification = $this->forUser($user)->whereKey($id)->first();

        if ($notification === null) {
            return false;
        }

        $notification->markAsRead();

        return true;
    }

    public function markAllRead(User $user): int
    {
        return $this->forUser($user)->unread()->update(['read_at' => now()]);
    }

    public function delete(User $user, int $id): bool
    {
        return $this->forUser($user)->whereKey($id)->delete() > 0;
    }

    /**
     * Apakah pengguna boleh melihat konten sehingga layak diberi notifikasi
     * konten (wisatawan & pengelola; admin panel tetap menerima).
     */
    public function canReceiveContentNotifications(User $user): bool
    {
        return $user->role !== null;
    }

    /*
    |--------------------------------------------------------------------------
    | Bantuan internal
    |--------------------------------------------------------------------------
    */

    /**
     * @return Builder<AppNotification>
     */
    private function forUser(User $user): Builder
    {
        return AppNotification::query()->where('user_id', $user->getKey());
    }

    /**
     * Pemilik/pengelola konten (kolom FK berbeda per modul).
     */
    public function ownerOf(Model $content): ?User
    {
        $ownerId = match (true) {
            $content instanceof Destination => $content->manager_id,
            $content instanceof UmkmBusiness => $content->owner_id,
            $content instanceof Accommodation => $content->manager_id,
            $content instanceof CultureArticle => $content->author_id,
            $content instanceof Event => $content->organizer_id,
            default => null,
        };

        if (blank($ownerId)) {
            return null;
        }

        return User::query()->find($ownerId);
    }

    public function contentTitle(Model $content): string
    {
        return (string) ($content->getAttribute('name')
            ?? $content->getAttribute('title')
            ?? class_basename($content));
    }

    /**
     * URL publik konten (bila ada).
     */
    public function contentUrl(Model $content): ?string
    {
        return match (true) {
            $content instanceof Destination => route('destinations.show', $content->slug),
            $content instanceof UmkmBusiness => route('umkm.show', $content->slug),
            $content instanceof Accommodation => route('accommodations.show', $content->slug),
            $content instanceof CultureArticle => route('culture.show', $content->slug),
            $content instanceof Event => route('events.show', $content->slug),
            default => null,
        };
    }

    /**
     * Tautan default untuk notifikasi moderasi (menuju dasbor pengelola).
     */
    private function moderationUrl(): ?string
    {
        return '/admin';
    }

    private function iconFor(string $type): string
    {
        return match ($type) {
            AppNotification::TYPE_MODERATION => 'information-circle',
            AppNotification::TYPE_EVENT => 'calendar-days',
            AppNotification::TYPE_HIGHLIGHT => 'sparkles',
            AppNotification::TYPE_AI => 'sparkles',
            default => 'bell',
        };
    }
}
