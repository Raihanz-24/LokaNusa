<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageEvents);
    }

    public function view(User $user, Event $event): bool
    {
        return $this->update($user, $event) || $user->hasPermission(Permission::ModerateContent);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageEvents);
    }

    public function update(User $user, Event $event): bool
    {
        if (! $user->hasPermission(Permission::ManageEvents)) {
            return false;
        }

        if ($user->managesAllContent()) {
            return true;
        }

        return $user->role === UserRole::PengelolaEvent
            && $event->organizer_id === $user->getKey();
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }

    public function moderate(User $user, Event $event): bool
    {
        return $user->hasPermission(Permission::ModerateContent);
    }
}
