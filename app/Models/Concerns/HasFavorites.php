<?php

namespace App\Models\Concerns;

use App\Models\Favorite;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Ditambahkan pada model konten yang dapat difavoritkan wisatawan.
 */
trait HasFavorites
{
    public function favorites(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    public function isFavoritedBy(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->favorites()->where('user_id', $user->getKey())->exists();
    }
}
