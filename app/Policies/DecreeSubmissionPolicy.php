<?php

namespace App\Policies;

use App\Models\DecreeSubmission;
use App\Models\User;

class DecreeSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('decree-submission.view')
            && $user->hasAnyRole([User::ROLE_ADMIN_INDUK, User::ROLE_ADMIN_SEKOLAH_MADRASAH]);
    }

    public function view(User $user, DecreeSubmission $submission): bool
    {
        return $this->viewAny($user)
            && ($user->isAdminInduk() || $user->accessibleSchoolIds()->contains($submission->school_id));
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH)
            && $user->can('decree-submission.create');
    }

    public function update(User $user, DecreeSubmission $submission): bool
    {
        return $user->isAdminInduk() && $user->can('decree-submission.update');
    }

    public function delete(User $user, DecreeSubmission $submission): bool
    {
        return $user->isAdminInduk() && $user->can('decree-submission.delete');
    }

    public function downloadResult(User $user, DecreeSubmission $submission): bool
    {
        return $this->view($user, $submission)
            && $submission->status === DecreeSubmission::STATUS_COMPLETED
            && filled($submission->result_file_path);
    }
}
