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
            && ($user->isAdminInduk() || $user->accessibleSchoolIds()->contains($request->school_id));
    }

    public function create(User $user): bool
    {
        return $user->can('document.create');
    }

    public function update(User $user, CorrespondenceRequest $request): bool
    {
        return $user->can('document.update');
    }

    public function delete(User $user, CorrespondenceRequest $request): bool
    {
        return $user->can('document.delete');
    }
}
