<?php

return [
    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_UPLOAD_DISK', 'local'),
        'rules' => ['required', 'file', 'max:819200'],
        'directory' => null,
        'middleware' => 'throttle:60,1',
        'preview_mimes' => ['png', 'gif', 'bmp', 'svg', 'wav', 'mp4', 'mov', 'avi', 'wmv', 'mp3', 'm4a', 'jpg', 'jpeg', 'mpga', 'webp', 'wma'],
        'max_upload_time' => 60,
        'cleanup' => true,
    ],
];
