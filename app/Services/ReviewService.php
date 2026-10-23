<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Models\Review;
use App\Models\User;

/**
 * Business logic ulasan wisatawan (Step 13 — Engagement).
 *
 * Ulasan **langsung terbit** (tanpa antrean moderasi). Super admin tetap
 * dapat menghapus ulasan yang melanggar (mis. mengandung unsur SARA).
 */
class ReviewService
{
    /**
     * Kirim/perbarui ulasan (langsung terbit).
     */
    public function submit(User $user, string $reviewableType, int $reviewableId, int $rating, ?string $comment): Review
    {
        return Review::query()->updateOrCreate(
            [
                'user_id' => $user->getKey(),
                'reviewable_type' => $reviewableType,
                'reviewable_id' => $reviewableId,
            ],
            [
                'rating' => $rating,
                'comment' => $comment,
                'status' => ContentStatus::Published,
                'published_at' => now(),
                'rejection_reason' => null,
            ],
        );
    }

    /**
     * Hapus ulasan (mis. karena melanggar aturan / SARA).
     */
    public function delete(Review $review): void
    {
        $review->delete();
    }

    /**
     * Sembunyikan ulasan yang melanggar tanpa menghapus datanya.
     *
     * Status diubah menjadi `rejected` + alasan dicatat, sehingga ulasan
     * hilang dari tampilan publik namun bukti tetap tersimpan.
     */
    public function hide(Review $review, string $reason): Review
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new \InvalidArgumentException('Alasan penyembunyian wajib diisi.');
        }

        $review->status = ContentStatus::Rejected;
        $review->rejection_reason = $reason;
        $review->published_at = null;
        $review->save();

        return $review;
    }

    /**
     * Terbitkan kembali ulasan yang sebelumnya disembunyikan.
     */
    public function publish(Review $review): Review
    {
        $review->status = ContentStatus::Published;
        $review->rejection_reason = null;
        $review->published_at = now();
        $review->save();

        return $review;
    }
}
