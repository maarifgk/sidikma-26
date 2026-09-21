<?php

namespace App\Policies;

use App\Models\DecreeCorrectionRequest;
use App\Models\User;

class DecreeCorrectionRequestPolicy
{
    public function viewAny(User $user): bool { return $user->can('decree-correction.view') && $user->hasAnyRole([User::ROLE_ADMIN_INDUK, User::ROLE_ADMIN_SEKOLAH_MADRASAH]); }
    public function view(User $user, DecreeCorrectionRequest $record): bool { return $this->viewAny($user) && ($user->isAdminInduk() || $user->accessibleSchoolIds()->contains($record->school_id)); }
    public function create(User $user): bool { return $user->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH) && $user->can('decree-correction.create'); }
    public function update(User $user, DecreeCorrectionRequest $record): bool
    {
        if ($user->isAdminInduk()) {
            return $user->can('decree-correction.view');
        }

        return $user->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH)
            && $user->accessibleSchoolIds()->contains($record->school_id)
            && in_array($record->status, [DecreeCorrectionRequest::STATUS_DRAFT, DecreeCorrectionRequest::STATUS_REVISION], true);
    }
    public function delete(User $user, DecreeCorrectionRequest $record): bool { return $this->update($user, $record) && $record->status === DecreeCorrectionRequest::STATUS_DRAFT; }
}
