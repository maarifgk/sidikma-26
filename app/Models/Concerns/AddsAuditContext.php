<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\Models\Activity;

trait AddsAuditContext
{
    public function tapActivity(Activity $activity, string $eventName): void
    {
        if (blank(config('activitylog.database_connection'))) {
            $activity->setConnection($this->getConnectionName() ?: config('database.default'));
        }

        $properties = $activity->properties ?? collect();

        $activity->properties = $properties->merge([
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole()
                ? null
                : str(request()->userAgent())->limit(500)->toString(),
        ]);
    }
}
