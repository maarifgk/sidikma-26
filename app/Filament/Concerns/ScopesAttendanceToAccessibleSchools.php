<?php

namespace App\Filament\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait ScopesAttendanceToAccessibleSchools
{
    public static function canUseAttendanceManagement(): bool
    {
        return auth()->user()?->hasAnyRole([
            User::ROLE_ADMIN_INDUK,
            User::ROLE_ADMIN_SEKOLAH_MADRASAH,
        ]) ?? false;
    }

    /** @return Collection<int, int> */
    protected function accessibleAttendanceSchoolIds(): Collection
    {
        return auth()->user()?->accessibleSchoolIds() ?? collect();
    }

    protected function scopeAttendanceQuery(Builder $query): Builder
    {
        return $query->whereIn('school_id', $this->accessibleAttendanceSchoolIds());
    }

    protected function attendancePanelId(): string
    {
        return filament()->getCurrentPanel()?->getId() ?? 'admin';
    }
}
