<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.default') !== 'sqlite' || ! str_contains(config('database.connections.sqlite.database'), 'browser-smoke-')) {
    throw new RuntimeException('Browser tests require a dedicated SQLite database.');
}
echo json_encode(App\Models\AttendanceSetting::where('school_id', 2)->firstOrFail()->only([
    'school_id', 'office_latitude', 'office_longitude', 'radius_meters', 'geofence_polygon',
]));
