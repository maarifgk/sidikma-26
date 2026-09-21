<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;

class ComplaintPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdminInduk() || $user->hasRole(User::ROLE_GURU_PEGAWAI);
    }

    public function view(User $user, Complaint $complaint): bool
    {
        return $user->isAdminInduk() || $complaint->submitted_by === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_GURU_PEGAWAI);
    }

    public function update(User $user, Complaint $complaint): bool
    {
        return $user->isAdminInduk();
    }

    public function delete(User $user, Complaint $complaint): bool
    {
        return false;
    }
}
