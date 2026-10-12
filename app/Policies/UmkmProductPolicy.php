<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\UmkmProduct;
use App\Models\User;

class UmkmProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageUmkm);
    }

    public function view(User $user, UmkmProduct $product): bool
    {
        return $this->update($user, $product);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageUmkm);
    }

    public function update(User $user, UmkmProduct $product): bool
    {
        if (! $user->hasPermission(Permission::ManageUmkm)) {
            return false;
        }

        if ($user->managesAllContent()) {
            return true;
        }

        return $product->business?->owner_id === $user->getKey();
    }

    public function delete(User $user, UmkmProduct $product): bool
    {
        return $this->update($user, $product);
    }
}
