<?php

namespace App\Policies;

use App\Models\BatikOrder;
use App\Models\Foundation;
use App\Models\User;

class BatikOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('foundation.view')
            && $user->hasAnyRole([
                User::ROLE_ADMIN_INDUK,
                User::ROLE_ADMIN_SEKOLAH_MADRASAH,
            ]);
    }

    public function view(User $user, BatikOrder $order): bool
    {
        return $this->viewAny($user)
            && ($user->isAdminInduk() || $user->accessibleSchoolIds()->contains($order->school_id));
    }

    public function create(User $user): bool
    {
        return $user->isAdminInduk()
            ? $user->can('update', Foundation::application())
            : $user->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH) && $user->can('school.update');
    }

    public function delete(User $user, BatikOrder $order): bool
    {
        return $user->isAdminInduk() && $user->can('update', $order->foundation);
    }
}
