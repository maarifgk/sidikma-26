<?php

namespace App\Policies;

use App\Models\DecreeProposal;
use App\Models\User;

class DecreeProposalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('approval.view');
    }

    public function view(User $user, DecreeProposal $decreeProposal): bool
    {
        if (! $user->can('approval.view')) {
            return false;
        }

        return $user->isAdminInduk()
            || $user->accessibleSchoolIds()->contains($decreeProposal->employee?->school_id);
    }

    public function create(User $user): bool
    {
        return $user->can('approval.create');
    }

    public function update(User $user, DecreeProposal $decreeProposal): bool
    {
        return $user->can('approval.update');
    }

    public function delete(User $user, DecreeProposal $decreeProposal): bool
    {
        return $user->can('approval.delete');
    }

    public function restore(User $user, DecreeProposal $decreeProposal): bool
    {
        return $user->can('approval.delete');
    }

    public function forceDelete(User $user, DecreeProposal $decreeProposal): bool
    {
        return false;
    }
}
