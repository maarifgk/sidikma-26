<?php

namespace App\Policies;

use App\Models\DecreeProposal;
use App\Models\School;
use App\Models\User;

class DecreeProposalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('approval.view')
            && ($user->isAdminInduk() || $user->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH));
    }

    public function view(User $user, DecreeProposal $decreeProposal): bool
    {
        if (! $user->can('approval.view')) {
            return false;
        }

        return $user->isAdminInduk() || $this->belongsToAccessibleSchool($user, $decreeProposal);
    }

    public function create(User $user): bool
    {
        return $user->can('approval.create');
    }

    public function update(User $user, DecreeProposal $decreeProposal): bool
    {
        return $user->can('approval.update')
            && $this->canAccessProposal($user, $decreeProposal);
    }

    public function edit(User $user, DecreeProposal $decreeProposal): bool
    {
        if ($decreeProposal->status !== DecreeProposal::STATUS_SUBMITTED || filled($decreeProposal->employee_id)) {
            return false;
        }

        if ($user->isAdminInduk()) {
            return $user->can('approval.update');
        }

        return $user->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH)
            && $user->can('approval.view')
            && $this->belongsToAccessibleSchool($user, $decreeProposal);
    }

    public function delete(User $user, DecreeProposal $decreeProposal): bool
    {
        return $user->can('approval.delete')
            && $this->canAccessProposal($user, $decreeProposal);
    }

    public function restore(User $user, DecreeProposal $decreeProposal): bool
    {
        return $user->can('approval.delete')
            && $this->canAccessProposal($user, $decreeProposal);
    }

    public function forceDelete(User $user, DecreeProposal $decreeProposal): bool
    {
        return false;
    }

    private function canAccessProposal(User $user, DecreeProposal $decreeProposal): bool
    {
        return $user->isAdminInduk() || $this->belongsToAccessibleSchool($user, $decreeProposal);
    }

    private function belongsToAccessibleSchool(User $user, DecreeProposal $proposal): bool
    {
        $schoolId = $proposal->employee?->school_id ?? $proposal->candidateSchoolId();

        if ($schoolId === null || ! $user->accessibleSchoolIds()->contains($schoolId)) {
            return false;
        }

        $snapshotFoundationId = $proposal->candidateFoundationId();

        if ($snapshotFoundationId === null || $proposal->candidateSchoolId() === null) {
            return true;
        }

        return School::query()
            ->whereKey($schoolId)
            ->where('foundation_id', $snapshotFoundationId)
            ->exists();
    }
}
