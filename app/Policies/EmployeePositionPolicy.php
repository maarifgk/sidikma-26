<?php

namespace App\Policies;

use App\Models\EmployeePosition;
use App\Models\User;

class EmployeePositionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdminInduk() && $user->can('position.view');
    }

    public function view(User $user, EmployeePosition $position): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdminInduk() && $user->can('position.create');
    }

    public function update(User $user, EmployeePosition $position): bool
    {
        return $user->isAdminInduk() && $user->can('position.update');
    }

    public function delete(User $user, EmployeePosition $position): bool
    {
        return $user->isAdminInduk() && $user->can('position.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdminInduk() && $user->can('position.delete');
    }

    public function restore(User $user, EmployeePosition $position): bool
    {
        return $this->delete($user, $position);
    }

    public function restoreAny(User $user): bool
    {
        return $this->deleteAny($user);
    }

    public function forceDelete(User $user, EmployeePosition $position): bool
    {
        return $this->delete($user, $position) && ! $position->assignments()->exists();
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
