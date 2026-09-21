<?php

namespace App\Policies;

use App\Models\Foundation;
use App\Models\FoundationAnnualReport;
use App\Models\User;

class FoundationAnnualReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('foundation.view');
    }

    public function view(User $user, FoundationAnnualReport $report): bool
    {
        return $user->can('view', $report->foundation);
    }

    public function create(User $user): bool
    {
        return $user->can('update', Foundation::application());
    }

    public function update(User $user, FoundationAnnualReport $report): bool
    {
        return $user->can('update', $report->foundation);
    }

    public function delete(User $user, FoundationAnnualReport $report): bool
    {
        return $user->can('update', $report->foundation);
    }
}
