<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageUsers);
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermission(Permission::ManageUsers);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageUsers);
    }

    public function update(User $user, User $model): bool
    {
        // Hanya Super Admin yang boleh mengubah akun Super Admin lain.
        if ($model->isSuperAdmin() && ! $user->isSuperAdmin()) {
            return false;
        }

        return $user->hasPermission(Permission::ManageUsers);
    }

    public function delete(User $user, User $model): bool
    {
        // Tidak boleh menghapus akun sendiri.
        if ($user->is($model)) {
            return false;
        }

        // Hanya Super Admin yang boleh menghapus akun Super Admin lain.
        if ($model->isSuperAdmin() && ! $user->isSuperAdmin()) {
            return false;
        }

        return $user->hasPermission(Permission::ManageUsers);
    }
}
