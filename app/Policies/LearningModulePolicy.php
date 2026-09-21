<?php

namespace App\Policies;

use App\Models\Foundation;
use App\Models\LearningModule;
use App\Models\User;

class LearningModulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('foundation.view');
    }

    public function view(User $user, LearningModule $module): bool
    {
        return $user->can('view', $module->foundation);
    }

    public function create(User $user): bool
    {
        return $user->can('update', Foundation::application());
    }

    public function delete(User $user, LearningModule $module): bool
    {
        return $user->can('update', $module->foundation);
    }
}
