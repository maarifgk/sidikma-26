<?php

namespace App\Policies;

use App\Models\Foundation;
use App\Models\PaymentFeeItem;
use App\Models\User;

class PaymentFeeItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('foundation.view');
    }

    public function updateAny(User $user): bool
    {
        return $user->can('update', Foundation::application());
    }

    public function update(User $user, PaymentFeeItem $feeItem): bool
    {
        return $user->can('update', $feeItem->foundation);
    }
}
