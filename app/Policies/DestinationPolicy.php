<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Destination;
use App\Models\User;

class DestinationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageDestinations);
    }

    public function view(User $user, Destination $destination): bool
    {
        return $this->update($user, $destination) || $user->hasPermission(Permission::ModerateContent);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageDestinations);
    }

    public function update(User $user, Destination $destination): bool
    {
        if (! $user->hasPermission(Permission::ManageDestinations)) {
            return false;
        }

        // Admin boleh mengubah semua destinasi.
        if ($user->managesAllContent()) {
            return true;
        }

        return $user->role === UserRole::PemilikDestinasi
            && $destination->manager_id === $user->getKey();
    }

    public function delete(User $user, Destination $destination): bool
    {
        return $user->managesAllContent() && $user->hasPermission(Permission::ManageDestinations);
    }

    /**
     * Menyetujui / menolak / mengarsipkan destinasi.
     */
    public function moderate(User $user, Destination $destination): bool
    {
        return $user->hasPermission(Permission::ModerateContent);
    }
}
