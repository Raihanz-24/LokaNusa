<?php

namespace App\Services\Concerns;

use App\Enums\ContentStatus;
use App\Models\Event;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Logika alur moderasi generik untuk model yang memiliki kolom
 * `status` (cast ke {@see ContentStatus}), `published_at`, dan `rejection_reason`.
 *
 * Setiap transisi juga memicu notifikasi in-app (dua arah): moderator diberi
 * tahu saat ada pengajuan, sedangkan pemilik konten diberi tahu hasil moderasi.
 */
trait ModeratesContent
{
    public function submitForReview(Model $model): Model
    {
        $this->assertTransition($model, [ContentStatus::Draft, ContentStatus::Rejected]);
        $model->status = ContentStatus::Pending;
        $model->rejection_reason = null;
        $model->save();

        app(NotificationService::class)->contentSubmitted($model, auth()->user());

        return $model;
    }

    public function approve(Model $model, ?User $approver = null): Model
    {
        $model = DB::transaction(function () use ($model): Model {
            $this->assertTransition($model, [ContentStatus::Pending, ContentStatus::Draft, ContentStatus::Archived]);
            $model->status = ContentStatus::Published;
            $model->published_at = now();
            $model->rejection_reason = null;
            $model->save();

            return $model;
        });

        $notifications = app(NotificationService::class);
        $notifications->contentApproved($model);

        // Event yang baru terbit juga diberitahukan ke seluruh pengguna.
        if ($model instanceof Event) {
            $notifications->eventPublished($model);
        }

        return $model;
    }

    public function reject(Model $model, string $reason): Model
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('Alasan penolakan wajib diisi.');
        }

        $this->assertTransition($model, [ContentStatus::Pending, ContentStatus::Draft]);
        $model->status = ContentStatus::Rejected;
        $model->rejection_reason = $reason;
        $model->save();

        app(NotificationService::class)->contentRejected($model, $reason);

        return $model;
    }

    public function archive(Model $model): Model
    {
        $this->assertTransition($model, [
            ContentStatus::Published,
            ContentStatus::Pending,
            ContentStatus::Draft,
            ContentStatus::Rejected,
        ]);
        $model->status = ContentStatus::Archived;
        $model->save();

        return $model;
    }

    /**
     * @param  array<int, ContentStatus>  $allowed
     */
    private function assertTransition(Model $model, array $allowed): void
    {
        if (! in_array($model->status, $allowed, true)) {
            throw new InvalidArgumentException(
                "Transisi status tidak diizinkan dari {$model->status->label()}."
            );
        }
    }
}
