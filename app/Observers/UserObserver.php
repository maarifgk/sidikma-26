<?php

namespace App\Observers;

use App\Models\User;
use App\Services\ApplicationNotificationService;

class UserObserver
{
    public function __construct(
        private readonly ApplicationNotificationService $notifications,
    ) {}

    public function updated(User $user): void
    {
        $importantFields = collect(['name', 'email', 'password', 'is_active'])
            ->filter(fn (string $field): bool => $user->wasChanged($field))
            ->values()
            ->all();

        $this->notifications->notifyImportantAccountChange(
            $user,
            $importantFields,
            auth()->user() instanceof User ? auth()->user() : $user->updatedBy,
        );
    }
}
