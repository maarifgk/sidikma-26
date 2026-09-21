<?php

return [
    'timezone' => env('ATTENDANCE_TIMEZONE', 'Asia/Jakarta'),
    'maximum_accuracy_meters' => (float) env('ATTENDANCE_MAXIMUM_ACCURACY_METERS', 100),
    'minimum_accuracy_tolerance_meters' => (float) env('ATTENDANCE_MINIMUM_ACCURACY_TOLERANCE_METERS', 100),
    'default_radius_meters' => (int) env('ATTENDANCE_DEFAULT_RADIUS_METERS', 100),
];
