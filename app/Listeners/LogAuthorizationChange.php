<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\ApplicationNotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Permission\Events\PermissionAttachedEvent;
use Spatie\Permission\Events\PermissionDetachedEvent;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;

class LogAuthorizationChange
{
    public function __construct(
        private readonly ApplicationNotificationService $notifications,
    ) {}

    public function handle(
        RoleAttachedEvent|RoleDetachedEvent|PermissionAttachedEvent|PermissionDetachedEvent $event,
    ): void {
        [$eventName, $property, $items, $description] = match (true) {
            $event instanceof RoleAttachedEvent => [
                'role_attached', 'roles', $event->rolesOrIds, 'Role diberikan',
            ],
            $event instanceof RoleDetachedEvent => [
                'role_detached', 'roles', $event->rolesOrIds, 'Role dicabut',
            ],
            $event instanceof PermissionAttachedEvent => [
                'permission_attached', 'permissions', $event->permissionsOrIds, 'Permission diberikan',
            ],
            default => [
                'permission_detached', 'permissions', $event->permissionsOrIds, 'Permission dicabut',
            ],
        };

        activity('authorization')
            ->event($eventName)
            ->performedOn($event->model)
            ->withProperties([
                $property => $this->normalizeItems($items),
            ])
            ->log($description);

        if ($event->model instanceof User) {
            $this->notifications->notifyImportantAccountChange(
                $event->model,
                [$property],
                auth()->user() instanceof User ? auth()->user() : null,
            );
        }
    }

    /** @return array<int, int|string> */
    private function normalizeItems(mixed $items): array
    {
        return collect($items instanceof Collection ? $items->all() : $items)
            ->flatten()
            ->map(fn (mixed $item): int|string => $item instanceof Model
                ? ($item->getAttribute('name') ?? $item->getKey())
                : $item)
            ->values()
            ->all();
    }
}
