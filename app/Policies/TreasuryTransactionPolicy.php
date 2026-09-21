<?php

namespace App\Policies;

use App\Models\Foundation;
use App\Models\TreasuryTransaction;
use App\Models\User;

class TreasuryTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('foundation.view');
    }

    public function view(User $user, TreasuryTransaction $transaction): bool
    {
        return $user->can('view', $transaction->foundation);
    }

    public function create(User $user): bool
    {
        return $user->can('update', Foundation::application());
    }

    public function update(User $user, TreasuryTransaction $transaction): bool
    {
        return $user->can('update', $transaction->foundation);
    }

    public function delete(User $user, TreasuryTransaction $transaction): bool
    {
        return $user->can('update', $transaction->foundation);
    }
}
