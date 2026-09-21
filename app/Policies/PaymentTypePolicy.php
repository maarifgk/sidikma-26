<?php

namespace App\Policies;

use App\Models\PaymentType;
use App\Models\User;

class PaymentTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('foundation.view');
    }

    public function view(User $user, PaymentType $paymentType): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdminInduk() && $user->can('foundation.update');
    }

    public function update(User $user, PaymentType $paymentType): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, PaymentType $paymentType): bool
    {
        return $this->create($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->create($user);
    }
}
