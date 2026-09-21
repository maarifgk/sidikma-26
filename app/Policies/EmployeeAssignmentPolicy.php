<?php

namespace App\Policies;

use App\Models\EmployeeAssignment;
use App\Models\User;

class EmployeeAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdminInduk() && $user->can('assignment.view');
    }

    public function view(User $user, EmployeeAssignment $assignment): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdminInduk() && $user->can('assignment.create');
    }

    public function update(User $user, EmployeeAssignment $assignment): bool
    {
        return $user->isAdminInduk() && $user->can('assignment.update');
    }

    public function delete(User $user, EmployeeAssignment $assignment): bool
    {
        return $user->isAdminInduk() && $user->can('assignment.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdminInduk() && $user->can('assignment.delete');
    }

    public function restore(User $user, EmployeeAssignment $assignment): bool
    {
        return $this->delete($user, $assignment);
    }

    public function restoreAny(User $user): bool
    {
        return $this->deleteAny($user);
    }

    public function forceDelete(User $user, EmployeeAssignment $assignment): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
