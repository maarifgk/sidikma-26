<?php

namespace App\Policies;

use App\Models\ProposalRequest;
use App\Models\User;

class ProposalRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('approval.view');
    }

    public function view(User $user, ProposalRequest $request): bool
    {
        return $user->can('approval.view')
            && ($user->isAdminInduk() || $user->accessibleSchoolIds()->contains($request->school_id));
    }

    public function create(User $user): bool
    {
        return $user->can('approval.create');
    }

    public function update(User $user, ProposalRequest $request): bool
    {
        return $user->can('approval.update');
    }

    public function review(User $user, ProposalRequest $request): bool
    {
        return $user->isAdminInduk() && $user->can('approval.update');
    }

    public function delete(User $user, ProposalRequest $request): bool
    {
        return $user->can('approval.delete');
    }
}
