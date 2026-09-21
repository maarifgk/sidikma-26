<?php

namespace App\Policies;

use App\Models\EmployeeMutation;
use App\Models\User;

class EmployeeMutationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('approval.view');
    }

    public function view(User $user, EmployeeMutation $employeeMutation): bool
    {
        if (! $user->can('approval.view')) {
            return false;
        }

        $schoolIds = $user->accessibleSchoolIds();

        return $user->isAdminInduk()
            || $employeeMutation->submitted_by === $user->getKey()
            || $schoolIds->contains($employeeMutation->origin_school_id)
            || $schoolIds->contains($employeeMutation->destination_school_id);
    }

    public function create(User $user): bool
    {
        return $user->can('approval.create');
    }

    public function update(User $user, EmployeeMutation $employeeMutation): bool
    {
        return $user->can('approval.update');
    }

    public function delete(User $user, EmployeeMutation $employeeMutation): bool
    {
        return $user->can('approval.delete');
    }
}
