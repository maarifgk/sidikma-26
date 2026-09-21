<?php

namespace App\Policies;

use App\Models\SipinterUpdate;
use App\Models\User;

class SipinterUpdatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('document.view');
    }

    public function view(User $user, SipinterUpdate $sipinterUpdate): bool
    {
        return $user->can('document.view')
            && ($user->isAdminInduk() || $user->accessibleSchoolIds()->contains($sipinterUpdate->school_id));
    }

    public function create(User $user): bool
    {
        return $user->can('document.create');
    }

    public function update(User $user, SipinterUpdate $sipinterUpdate): bool
    {
        return $user->can('document.update');
    }

    public function delete(User $user, SipinterUpdate $sipinterUpdate): bool
    {
        return $user->can('document.delete');
    }
}
