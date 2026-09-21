<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdminInduk() && $user->can('user.view');
    }

    public function view(User $user, User $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdminInduk() && $user->can('user.create');
    }

    public function update(User $user, User $record): bool
    {
        return $user->isAdminInduk() && $user->can('user.update');
    }

    public function delete(User $user, User $record): bool
    {
        if (! $user->isAdminInduk() || ! $user->can('user.delete') || $user->is($record)) {
            return false;
        }

        if (! $record->isAdminInduk()) {
            return true;
        }

        return User::role(User::ROLE_ADMIN_INDUK)
            ->whereKeyNot($record->getKey())
            ->exists();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
