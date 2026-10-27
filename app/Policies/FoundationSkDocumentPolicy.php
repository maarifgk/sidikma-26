<?php
namespace App\Policies;
use App\Models\FoundationSkDocument;
use App\Models\User;
class FoundationSkDocumentPolicy
{
 public function viewAny(User $user): bool { return ($user->isAdminInduk() || $user->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH)) && ($user->isAdminInduk() || $user->can('sk-yayasan.view')); }
 public function view(User $user, FoundationSkDocument $document): bool { if (! $this->viewAny($user)) return false; if ($user->isAdminInduk()) return true; $schoolIds = $user->accessibleSchoolIds(); $owner = $document->user()->with('employee')->with('memberships')->first(); return $owner !== null && ($schoolIds->contains($owner->employee?->school_id) || $owner->memberships->contains(fn ($membership): bool => $membership->status === 'active' && $schoolIds->contains($membership->school_id))); }
 public function create(User $user): bool { return $user->isAdminInduk(); }
 public function update(User $user, FoundationSkDocument $document): bool { return $this->create($user) && ($user->isAdminInduk() || $user->accessibleSchoolIds()->contains($document->user?->employee?->school_id)); }
 public function delete(User $user, FoundationSkDocument $document): bool { return $this->update($user,$document); }
}
