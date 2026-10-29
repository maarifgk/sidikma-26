<?php

namespace App\Policies;

use App\Models\CorrespondenceRequest;
use App\Models\User;

class CorrespondenceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('document.view');
    }

    public function view(User $user, CorrespondenceRequest $request): bool
    {
        return $user->can('document.view')
            && $this->canAccessRequest($user, $request);
    }

    public function create(User $user): bool
    {
        return $user->can('document.create');
    }

    public function update(User $user, CorrespondenceRequest $request): bool
    {
        return $user->can('document.update')
            && $this->canAccessRequest($user, $request);
    }

    public function delete(User $user, CorrespondenceRequest $request): bool
    {
        return $user->can('document.delete')
            && $this->canAccessRequest($user, $request);
    }

    private function canAccessRequest(User $user, CorrespondenceRequest $request): bool
    {
        return $user->isAdminInduk()
            || (filled($request->school_id)
                && $user->accessibleSchoolIds()->contains($request->school_id));
    }
}
