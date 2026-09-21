<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\Models\Activity;

trait AddsAuditContext
{
    public function tapActivity(Activity $activity, string $eventName): void
    {
        $properties = $activity->properties ?? collect();

        $activity->properties = $properties->merge([
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole()
                ? null
                : str(request()->userAgent())->limit(500)->toString(),
        ]);
    }
}
