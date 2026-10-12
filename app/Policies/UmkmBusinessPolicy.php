<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\UmkmBusiness;
use App\Models\User;

class UmkmBusinessPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageUmkm);
    }

    public function view(User $user, UmkmBusiness $business): bool
    {
        return $this->update($user, $business) || $user->hasPermission(Permission::ModerateContent);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageUmkm);
    }

    public function update(User $user, UmkmBusiness $business): bool
    {
        if (! $user->hasPermission(Permission::ManageUmkm)) {
            return false;
        }

        if ($user->managesAllContent()) {
            return true;
        }

        return $user->role === UserRole::PemilikUmkm
            && $business->owner_id === $user->getKey();
    }

    public function delete(User $user, UmkmBusiness $business): bool
    {
        return $user->managesAllContent() && $user->hasPermission(Permission::ManageUmkm);
    }

    public function moderate(User $user, UmkmBusiness $business): bool
    {
        return $user->hasPermission(Permission::ModerateContent);
    }
}
