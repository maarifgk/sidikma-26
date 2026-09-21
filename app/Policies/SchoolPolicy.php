<?php

namespace App\Policies;

use App\Models\School;
use App\Models\User;

class SchoolPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('school.view');
    }

    public function view(User $user, School $school): bool
    {
        return $user->can('school.view')
            && ($user->isAdminInduk() || $user->accessibleSchoolIds()->contains($school->getKey()));
    }

    public function create(User $user): bool
    {
        return $user->isAdminInduk() && $user->can('school.create');
    }

    public function update(User $user, School $school): bool
    {
        return $user->can('school.update')
            && ($user->isAdminInduk() || $user->accessibleSchoolIds()->contains($school->getKey()));
    }

    public function delete(User $user, School $school): bool
    {
        return $user->isAdminInduk() && $user->can('school.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdminInduk() && $user->can('school.delete');
    }

    public function restore(User $user, School $school): bool
    {
        return $this->delete($user, $school);
    }

    public function restoreAny(User $user): bool
    {
        return $this->deleteAny($user);
    }

    public function forceDelete(User $user, School $school): bool
    {
        return $this->delete($user, $school);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->deleteAny($user);
    }
}
