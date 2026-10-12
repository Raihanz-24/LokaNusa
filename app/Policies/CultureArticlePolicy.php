<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\CultureArticle;
use App\Models\User;

class CultureArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageCulture);
    }

    public function view(User $user, CultureArticle $article): bool
    {
        return $this->update($user, $article) || $user->hasPermission(Permission::ModerateContent);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageCulture);
    }

    public function update(User $user, CultureArticle $article): bool
    {
        if (! $user->hasPermission(Permission::ManageCulture)) {
            return false;
        }

        if ($user->managesAllContent()) {
            return true;
        }

        return $user->role === UserRole::KontributorBudaya
            && $article->author_id === $user->getKey();
    }

    public function delete(User $user, CultureArticle $article): bool
    {
        if (! $user->hasPermission(Permission::ManageCulture)) {
            return false;
        }

        if ($user->managesAllContent()) {
            return true;
        }

        return $user->role === UserRole::KontributorBudaya
            && $article->author_id === $user->getKey();
    }

    public function moderate(User $user, CultureArticle $article): bool
    {
        return $user->hasPermission(Permission::ModerateContent);
    }
}
