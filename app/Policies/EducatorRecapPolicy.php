<?php

namespace App\Policies;

use App\Models\EducatorRecap;
use App\Models\User;

class EducatorRecapPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('school.view');
    }

    public function view(User $user, EducatorRecap $recap): bool
    {
        return $user->can('school.view') && $this->canAccessRecap($user, $recap);
    }

    public function create(User $user): bool
    {
        return $user->can('school.update');
    }

    public function update(User $user, EducatorRecap $recap): bool
    {
        return $user->can('school.update') && $this->canAccessRecap($user, $recap);
    }

    public function delete(User $user, EducatorRecap $recap): bool
    {
        return $user->can('school.update') && $this->canAccessRecap($user, $recap);
    }

    private function canAccessRecap(User $user, EducatorRecap $recap): bool
    {
        return $user->isAdminInduk()
            || $user->accessibleSchoolIds()->contains($recap->school_id);
    }
}
