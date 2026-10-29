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
            && $this->canAccessRequest($user, $request);
    }

    public function create(User $user): bool
    {
        return $user->can('approval.create');
    }

    public function update(User $user, EmployeeActivityRequest $request): bool
    {
        return $user->can('approval.update')
            && $this->canAccessRequest($user, $request);
    }

    public function delete(User $user, EmployeeActivityRequest $request): bool
    {
        return $user->can('approval.delete')
            && $this->canAccessRequest($user, $request);
    }

    private function canAccessRequest(User $user, EmployeeActivityRequest $request): bool
    {
        return $user->isAdminInduk()
            || (filled($request->school_id)
                && $user->accessibleSchoolIds()->contains($request->school_id));
    }
}
