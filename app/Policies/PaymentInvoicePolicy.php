<?php

namespace App\Policies;

use App\Models\PaymentInvoice;
use App\Models\User;

class PaymentInvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('foundation.view');
    }

    public function view(User $user, PaymentInvoice $invoice): bool
    {
        if ($user->isAdminInduk()) {
            return true;
        }

        if ($user->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH)) {
            return $user->accessibleSchoolIds()->contains($invoice->school_id);
        }

        return $invoice->user_id === $user->getKey()
            || $invoice->employee?->user_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->isAdminInduk() && $user->can('foundation.update');
    }

    public function update(User $user, PaymentInvoice $invoice): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, PaymentInvoice $invoice): bool
    {
        return $this->create($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->create($user);
    }
}
