<?php

namespace App\Policies;

use App\Models\Foundation;
use App\Models\SecretariatAgenda;
use App\Models\User;

class SecretariatAgendaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('foundation.view');
    }

    public function view(User $user, SecretariatAgenda $agenda): bool
    {
        return $user->can('view', $agenda->foundation);
    }

    public function create(User $user): bool
    {
        return $user->can('update', Foundation::application());
    }

    public function update(User $user, SecretariatAgenda $agenda): bool
    {
        return $user->can('update', $agenda->foundation);
    }

    public function delete(User $user, SecretariatAgenda $agenda): bool
    {
        return $user->can('update', $agenda->foundation);
    }
}
