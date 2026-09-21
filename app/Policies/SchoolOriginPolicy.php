<?php

namespace App\Policies;

use App\Models\SchoolOrigin;
use App\Models\User;

class SchoolOriginPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdminInduk() && $user->can('school.view');
    }

    public function view(User $user, SchoolOrigin $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdminInduk() && $user->can('school.create');
    }

    public function update(User $user, SchoolOrigin $record): bool
    {
        return $user->isAdminInduk() && $user->can('school.update');
    }

    public function delete(User $user, SchoolOrigin $record): bool
    {
        return $user->isAdminInduk() && $user->can('school.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdminInduk() && $user->can('school.delete');
    }

    public function restore(User $user, SchoolOrigin $record): bool
    {
        return $this->delete($user, $record);
    }

    public function restoreAny(User $user): bool
    {
        return $this->deleteAny($user);
    }

    public function forceDelete(User $user, SchoolOrigin $record): bool
    {
        return $this->delete($user, $record);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->deleteAny($user);
    }
}
