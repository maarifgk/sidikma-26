<?php

namespace App\Policies;

use App\Models\BatikProduct;
use App\Models\User;

class BatikProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('foundation.view');
    }

    public function view(User $user, BatikProduct $product): bool
    {
        return $user->can('view', $product->foundation);
    }

    public function update(User $user, BatikProduct $product): bool
    {
        return $user->can('update', $product->foundation);
    }
}
