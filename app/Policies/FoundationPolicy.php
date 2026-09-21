<?php

namespace App\Policies;

use App\Models\Foundation;
use App\Models\User;

class FoundationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('foundation.view');
    }

    public function view(User $user, Foundation $foundation): bool
    {
        return $user->can('foundation.view')
            && ($user->isAdminInduk() || $user->accessibleFoundationIds()->contains($foundation->getKey()));
    }

    public function create(User $user): bool
    {
        return $user->isAdminInduk() && $user->can('foundation.create');
    }

    public function update(User $user, Foundation $foundation): bool
    {
        return $user->isAdminInduk() && $user->can('foundation.update');
    }

    public function delete(User $user, Foundation $foundation): bool
    {
        return $user->isAdminInduk() && $user->can('foundation.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdminInduk() && $user->can('foundation.delete');
    }

    public function restore(User $user, Foundation $foundation): bool
    {
        return $this->delete($user, $foundation);
    }

    public function restoreAny(User $user): bool
    {
        return $this->deleteAny($user);
    }

    public function forceDelete(User $user, Foundation $foundation): bool
    {
        return $this->delete($user, $foundation);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->deleteAny($user);
    }
}
