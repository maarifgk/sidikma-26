<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ApprovalRequest;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DashboardMetrics
{
    /** @return Builder<Foundation> */
    public function foundations(User $user): Builder
    {
        return Foundation::query()->accessibleTo($user);
    }

    /** @return Builder<School> */
    public function schools(User $user): Builder
    {
        return School::query()->accessibleTo($user);
    }

    /** @return Builder<Employee> */
    public function employees(User $user): Builder
    {
        if ($user->isAdminInduk()) {
            return Employee::query();
        }

        if ($user->hasRole(User::ROLE_GURU_PEGAWAI)) {
            return Employee::query()->where('user_id', $user->getKey());
        }

        if ($user->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH)) {
            return Employee::query()->whereIn('school_id', $user->accessibleSchoolIds());
        }

        return Employee::query()->whereRaw('1 = 0');
    }

    /** @return Builder<User> */
    public function users(User $user): Builder
    {
        if ($user->isAdminInduk()) {
            return User::query();
        }

        if ($user->hasRole(User::ROLE_GURU_PEGAWAI)) {
            return User::query()->whereKey($user->getKey());
        }

        if (! $user->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH)) {
            return User::query()->whereRaw('1 = 0');
        }

        $foundationIds = $user->accessibleFoundationIds();
        $schoolIds = $user->accessibleSchoolIds();

        return User::query()->where(function (Builder $query) use ($user, $foundationIds, $schoolIds): void {
            $query
                ->whereKey($user->getKey())
                ->orWhereHas('memberships', function (Builder $query) use ($foundationIds, $schoolIds): void {
                    $query
                        ->active()
                        ->where(function (Builder $query) use ($foundationIds, $schoolIds): void {
                            $query
                                ->whereIn('school_id', $schoolIds)
                                ->orWhere(function (Builder $query) use ($foundationIds): void {
                                    $query
                                        ->whereNull('school_id')
                                        ->whereIn('foundation_id', $foundationIds);
                                });
                        });
                });
        });
    }

    /** @return Builder<Document> */
    public function documents(User $user): Builder
    {
        if ($user->isAdminInduk()) {
            return Document::query();
        }

        $employeeIds = $this->employees($user)->select('id');
        $schoolIds = $this->schools($user)->select('id');
        $foundationIds = $this->foundations($user)->select('id');

        return Document::query()->where(function (Builder $query) use (
            $employeeIds,
            $schoolIds,
            $foundationIds,
        ): void {
            $query
                ->where(function (Builder $query) use ($employeeIds): void {
                    $query
                        ->where('owner_type', Employee::class)
                        ->whereIn('owner_id', $employeeIds);
                })
                ->orWhere(function (Builder $query) use ($schoolIds): void {
                    $query
                        ->where('owner_type', School::class)
                        ->whereIn('owner_id', $schoolIds);
                })
                ->orWhere(function (Builder $query) use ($foundationIds): void {
                    $query
                        ->where('owner_type', Foundation::class)
                        ->whereIn('owner_id', $foundationIds);
                });
        });
    }

    /** @return Builder<ApprovalRequest> */
    public function approvals(User $user): Builder
    {
        if ($user->isAdminInduk()) {
            return ApprovalRequest::query();
        }

        $employeeIds = $this->employees($user)->select('id');
        $schoolIds = $this->schools($user)->select('id');
        $foundationIds = $this->foundations($user)->select('id');
        $documentIds = $this->documents($user)->select('id');

        return ApprovalRequest::query()->where(function (Builder $query) use (
            $employeeIds,
            $schoolIds,
            $foundationIds,
            $documentIds,
        ): void {
            $query
                ->where(function (Builder $query) use ($employeeIds): void {
                    $query
                        ->where('approvable_type', Employee::class)
                        ->whereIn('approvable_id', $employeeIds);
                })
                ->orWhere(function (Builder $query) use ($schoolIds): void {
                    $query
                        ->where('approvable_type', School::class)
                        ->whereIn('approvable_id', $schoolIds);
                })
                ->orWhere(function (Builder $query) use ($foundationIds): void {
                    $query
                        ->where('approvable_type', Foundation::class)
                        ->whereIn('approvable_id', $foundationIds);
                })
                ->orWhere(function (Builder $query) use ($documentIds): void {
                    $query
                        ->where('approvable_type', Document::class)
                        ->whereIn('approvable_id', $documentIds);
                });
        });
    }

    /** @return Builder<Activity> */
    public function activities(User $user): Builder
    {
        return $user->isAdminInduk()
            ? Activity::query()
            : Activity::query()->whereRaw('1 = 0');
    }

    public function scopeLabel(User $user): string
    {
        if ($user->isAdminInduk()) {
            return 'Seluruh sekolah/madrasah';
        }

        $schoolNames = $this->schools($user)
            ->orderBy('name')
            ->limit(3)
            ->pluck('name');

        if ($schoolNames->isNotEmpty()) {
            return $schoolNames->implode(', ');
        }

        $foundationNames = $this->foundations($user)
            ->orderBy('name')
            ->limit(3)
            ->pluck('name');

        return $foundationNames->isNotEmpty()
            ? $foundationNames->implode(', ')
            : 'Belum memiliki penugasan aktif';
    }
}
