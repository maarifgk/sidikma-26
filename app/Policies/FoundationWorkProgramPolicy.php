<?php

namespace App\Policies;

use App\Models\Foundation;
use App\Models\FoundationWorkProgram;
use App\Models\User;

class FoundationWorkProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('foundation.view');
    }

    public function view(User $user, FoundationWorkProgram $workProgram): bool
    {
        return $user->can('view', $workProgram->foundation);
    }

    public function create(User $user): bool
    {
        return $user->can('update', Foundation::application());
    }

    public function update(User $user, FoundationWorkProgram $workProgram): bool
    {
        return $user->can('update', $workProgram->foundation);
    }

    public function delete(User $user, FoundationWorkProgram $workProgram): bool
    {
        return $user->can('update', $workProgram->foundation);
    }
}
