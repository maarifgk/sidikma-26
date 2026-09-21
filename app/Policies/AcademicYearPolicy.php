<?php

namespace App\Policies;

use App\Models\AcademicYear;
use App\Models\User;

class AcademicYearPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('foundation.view');
    }

    public function view(User $user, AcademicYear $academicYear): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdminInduk() && $user->can('foundation.update');
    }

    public function update(User $user, AcademicYear $academicYear): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, AcademicYear $academicYear): bool
    {
        return $this->create($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->create($user);
    }
}
