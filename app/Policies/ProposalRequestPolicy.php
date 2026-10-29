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
            && $this->canAccessRequest($user, $request);
    }

    public function create(User $user): bool
    {
        return $user->can('approval.create');
    }

    public function update(User $user, ProposalRequest $request): bool
    {
        return $user->can('approval.update')
            && $this->canAccessRequest($user, $request);
    }

    public function review(User $user, ProposalRequest $request): bool
    {
        return $user->isAdminInduk() && $user->can('approval.update');
    }

    public function delete(User $user, ProposalRequest $request): bool
    {
        return $user->can('approval.delete')
            && $this->canAccessRequest($user, $request);
    }

    private function canAccessRequest(User $user, ProposalRequest $request): bool
    {
        return $user->isAdminInduk()
            || (filled($request->school_id)
                && $user->accessibleSchoolIds()->contains($request->school_id));
    }
}
