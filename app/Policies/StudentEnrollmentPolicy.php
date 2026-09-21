<?php

namespace App\Policies;

use App\Models\StudentEnrollment;
use App\Models\User;

class StudentEnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('school.view');
    }

    public function view(User $user, StudentEnrollment $enrollment): bool
    {
        return $user->can('school.view') && $this->canAccessEnrollment($user, $enrollment);
    }

    public function create(User $user): bool
    {
        return $user->can('school.update');
    }

    public function update(User $user, StudentEnrollment $enrollment): bool
    {
        return $user->can('school.update') && $this->canAccessEnrollment($user, $enrollment);
    }

    public function delete(User $user, StudentEnrollment $enrollment): bool
    {
        return $user->can('school.update') && $this->canAccessEnrollment($user, $enrollment);
    }

    private function canAccessEnrollment(User $user, StudentEnrollment $enrollment): bool
    {
        return $user->isAdminInduk()
            || $user->accessibleSchoolIds()->contains($enrollment->school_id);
    }
}
