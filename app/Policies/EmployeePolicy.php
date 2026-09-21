<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employee.view');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $this->viewAny($user) && $this->canAccessEmployee($user, $employee);
    }

    public function create(User $user): bool
    {
        return $user->can('employee.create');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('employee.update') && $this->canAccessEmployee($user, $employee);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->isAdminInduk() && $user->can('employee.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdminInduk() && $user->can('employee.delete');
    }

    public function restore(User $user, Employee $employee): bool
    {
        return $this->delete($user, $employee);
    }

    public function restoreAny(User $user): bool
    {
        return $this->deleteAny($user);
    }

    public function forceDelete(User $user, Employee $employee): bool
    {
        return $this->delete($user, $employee) && ! $employee->assignments()->exists();
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    private function canAccessEmployee(User $user, Employee $employee): bool
    {
        return $user->isAdminInduk()
            || $user->accessibleSchoolIds()->contains($employee->school_id);
    }
}
