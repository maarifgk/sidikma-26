<?php

namespace App\Policies;

use App\Models\EmployeeActivityRequest;
use App\Models\User;

class EmployeeActivityRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('approval.view');
    }

    public function view(User $user, EmployeeActivityRequest $request): bool
    {
        return $user->can('approval.view')
            && ($user->isAdminInduk() || $user->accessibleSchoolIds()->contains($request->school_id));
    }

    public function create(User $user): bool
    {
        return $user->can('approval.create');
    }

    public function update(User $user, EmployeeActivityRequest $request): bool
    {
        return $user->can('approval.update');
    }

    public function delete(User $user, EmployeeActivityRequest $request): bool
    {
        return $user->can('approval.delete');
    }
}
