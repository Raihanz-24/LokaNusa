<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Accommodation;
use App\Models\User;

class AccommodationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageAccommodations);
    }

    public function view(User $user, Accommodation $accommodation): bool
    {
        return $this->update($user, $accommodation) || $user->hasPermission(Permission::ModerateContent);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageAccommodations);
    }

    public function update(User $user, Accommodation $accommodation): bool
    {
        if (! $user->hasPermission(Permission::ManageAccommodations)) {
            return false;
        }

        if ($user->managesAllContent()) {
            return true;
        }

        return $user->role === UserRole::PemilikPenginapan
            && $accommodation->manager_id === $user->getKey();
    }

    public function delete(User $user, Accommodation $accommodation): bool
    {
        return $user->managesAllContent() && $user->hasPermission(Permission::ManageAccommodations);
    }

    public function moderate(User $user, Accommodation $accommodation): bool
    {
        return $user->hasPermission(Permission::ModerateContent);
    }
}
