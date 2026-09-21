<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\SchoolHead;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return ($user->isAdminInduk() || $user->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH))
            && $user->can('document.view');
    }

    public function view(User $user, Document $document): bool
    {
        return ($user->isAdminInduk() && $user->can('document.view'))
            || $this->isAccessibleSchoolHeadDocument($user, $document)
            || $this->isAccessibleDecree($user, $document);
    }

    public function download(User $user, Document $document): bool
    {
        return ($user->isAdminInduk() && $user->can('document.download'))
            || ($user->hasRole(User::ROLE_GURU_PEGAWAI) && $this->isAccessibleDecree($user, $document))
            || ($user->can('document.download') && $this->isAccessibleSchoolHeadDocument($user, $document))
            || ($user->can('document.download') && $this->isAccessibleDecree($user, $document));
    }

    public function create(User $user): bool
    {
        return ($user->isAdminInduk() || $user->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH))
            && $user->can('document.create');
    }

    public function update(User $user, Document $document): bool
    {
        return ($user->isAdminInduk() && $user->can('document.update'))
            || ($user->can('document.update') && $this->isAccessibleSchoolHeadDocument($user, $document));
    }

    public function delete(User $user, Document $document): bool
    {
        return ($user->isAdminInduk() && $user->can('document.delete'))
            || ($user->can('document.delete') && $this->isAccessibleSchoolHeadDocument($user, $document));
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdminInduk() && $user->can('document.delete');
    }

    public function restore(User $user, Document $document): bool
    {
        return $this->delete($user, $document);
    }

    public function restoreAny(User $user): bool
    {
        return $this->deleteAny($user);
    }

    public function forceDelete(User $user, Document $document): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    private function isAccessibleDecree(User $user, Document $document): bool
    {
        if (! str_starts_with($document->document_type, 'foundation_decree:')
            || $document->owner_type !== User::class) {
            return false;
        }

        if ($user->hasRole(User::ROLE_GURU_PEGAWAI)) {
            return (int) $document->owner_id === (int) $user->getKey();
        }

        if (! $user->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH)) {
            return false;
        }

        $schoolIds = $user->accessibleSchoolIds();

        return User::query()
            ->whereKey($document->owner_id)
            ->where(function ($query) use ($schoolIds): void {
                $query
                    ->whereHas('employee', fn ($query) => $query->whereIn('school_id', $schoolIds))
                    ->orWhereHas('memberships', fn ($query) => $query
                        ->active()
                        ->whereIn('school_id', $schoolIds));
            })
            ->exists();
    }

    private function isAccessibleSchoolHeadDocument(User $user, Document $document): bool
    {
        if ($document->owner_type !== SchoolHead::class
            || ! $user->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH)) {
            return false;
        }

        return SchoolHead::query()
            ->accessibleTo($user)
            ->whereKey($document->owner_id)
            ->exists();
    }
}
