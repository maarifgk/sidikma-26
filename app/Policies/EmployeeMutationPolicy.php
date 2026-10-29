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

        return $this->canAccessMutation($user, $employeeMutation);
    }

    public function create(User $user): bool
    {
        return $user->can('approval.create');
    }

    public function update(User $user, EmployeeMutation $employeeMutation): bool
    {
        return $user->can('approval.update')
            && $this->canAccessMutation($user, $employeeMutation);
    }

    public function delete(User $user, EmployeeMutation $employeeMutation): bool
    {
        return $user->can('approval.delete')
            && $this->canAccessMutation($user, $employeeMutation);
    }

    private function canAccessMutation(User $user, EmployeeMutation $employeeMutation): bool
    {
        $schoolIds = $user->accessibleSchoolIds();

        return $user->isAdminInduk()
            || (filled($employeeMutation->origin_school_id)
                && $schoolIds->contains($employeeMutation->origin_school_id))
            || (filled($employeeMutation->destination_school_id)
                && $schoolIds->contains($employeeMutation->destination_school_id));
    }
}
