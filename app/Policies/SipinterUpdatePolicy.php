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
            && $this->canAccessUpdate($user, $sipinterUpdate);
    }

    public function create(User $user): bool
    {
        return $user->can('document.create');
    }

    public function update(User $user, SipinterUpdate $sipinterUpdate): bool
    {
        return $user->can('document.update')
            && $this->canAccessUpdate($user, $sipinterUpdate);
    }

    public function delete(User $user, SipinterUpdate $sipinterUpdate): bool
    {
        return $user->can('document.delete')
            && $this->canAccessUpdate($user, $sipinterUpdate);
    }

    private function canAccessUpdate(User $user, SipinterUpdate $sipinterUpdate): bool
    {
        return $user->isAdminInduk()
            || (filled($sipinterUpdate->school_id)
                && $user->accessibleSchoolIds()->contains($sipinterUpdate->school_id));
    }
}
