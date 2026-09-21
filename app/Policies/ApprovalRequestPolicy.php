<?php

namespace App\Policies;

use App\Models\ApprovalRequest;
use App\Models\User;

class ApprovalRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdminInduk() && $user->can('approval.view');
    }

    public function view(User $user, ApprovalRequest $approvalRequest): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdminInduk() && $user->can('approval.create');
    }

    public function update(User $user, ApprovalRequest $approvalRequest): bool
    {
        return $user->isAdminInduk() && $user->can('approval.update');
    }

    public function submit(User $user, ApprovalRequest $approvalRequest): bool
    {
        return $user->isAdminInduk() && $user->can('approval.submit');
    }

    public function verify(User $user, ApprovalRequest $approvalRequest): bool
    {
        return $user->isAdminInduk() && $user->can('approval.verify');
    }

    public function approve(User $user, ApprovalRequest $approvalRequest): bool
    {
        return $user->isAdminInduk() && $user->can('approval.approve');
    }

    public function reject(User $user, ApprovalRequest $approvalRequest): bool
    {
        return $user->isAdminInduk() && $user->can('approval.reject');
    }

    public function delete(User $user, ApprovalRequest $approvalRequest): bool
    {
        return $user->isAdminInduk()
            && $user->can('approval.delete')
            && in_array($approvalRequest->status, [
                ApprovalRequest::STATUS_DRAFT,
                ApprovalRequest::STATUS_REJECTED,
            ], true);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdminInduk() && $user->can('approval.delete');
    }

    public function restore(User $user, ApprovalRequest $approvalRequest): bool
    {
        return $this->delete($user, $approvalRequest);
    }

    public function restoreAny(User $user): bool
    {
        return $this->deleteAny($user);
    }

    public function forceDelete(User $user, ApprovalRequest $approvalRequest): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
