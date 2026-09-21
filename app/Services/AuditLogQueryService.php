<?php
namespace App\Services;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
class AuditLogQueryService
{
 public function forUser(User $user): Builder
 {
  abort_unless($user->hasAnyRole([User::ROLE_ADMIN_INDUK, User::ROLE_ADMIN_SEKOLAH_MADRASAH]), 403);
  return AuditLog::query()->when(! $user->hasRole(User::ROLE_ADMIN_INDUK), fn (Builder $q) => $q->whereIn('school_id', $user->accessibleSchoolIds()));
 }
}
