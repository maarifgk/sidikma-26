<?php
namespace App\Policies;
use App\Models\FoundationSkDocument;
use App\Models\User;
class FoundationSkDocumentPolicy
{
 public function viewAny(User $user): bool { return $user->hasAnyRole([User::ROLE_ADMIN_INDUK,User::ROLE_ADMIN_SEKOLAH_MADRASAH,User::ROLE_GURU_PEGAWAI]) && ($user->isAdminInduk() || $user->can('sk-yayasan.view')); }
 public function view(User $user, FoundationSkDocument $document): bool { return $this->viewAny($user) && ($user->isAdminInduk() || ($user->hasRole(User::ROLE_GURU_PEGAWAI) && $document->user_id===$user->id) || $user->accessibleSchoolIds()->contains($document->user?->employee?->school_id)); }
 public function create(User $user): bool { return $user->hasAnyRole([User::ROLE_ADMIN_INDUK,User::ROLE_ADMIN_SEKOLAH_MADRASAH]) && ($user->isAdminInduk() || $user->can('sk-yayasan.manage')); }
 public function update(User $user, FoundationSkDocument $document): bool { return $this->create($user) && ($user->isAdminInduk() || $user->accessibleSchoolIds()->contains($document->user?->employee?->school_id)); }
 public function delete(User $user, FoundationSkDocument $document): bool { return $this->update($user,$document); }
}
