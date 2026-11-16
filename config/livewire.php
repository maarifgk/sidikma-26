<?php

return [
    'temporary_file_upload' => [
        // Keep temporary uploads on the same private filesystem as the
        // administration templates. This avoids cross-disk move failures
        // when FileUpload is used inside the requirements repeater.
        'disk' => env('LIVEWIRE_UPLOAD_DISK', 'decree-proposals'),
        'rules' => ['required', 'file', 'max:819200'],
        'directory' => 'livewire-tmp',
        'middleware' => 'throttle:120,10',
        'preview_mimes' => ['png', 'gif', 'bmp', 'svg', 'wav', 'mp4', 'mov', 'avi', 'wmv', 'mp3', 'm4a', 'jpg', 'jpeg', 'mpga', 'webp', 'wma'],
        'max_upload_time' => 3600,
        'cleanup' => true,
    ],
];
