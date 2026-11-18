<?php

return [
    'temporary_file_upload' => [
        // Temporary uploads must use Livewire's local temporary filesystem.
        // The final FileUpload destination remains the private
        // `decree-proposals` disk configured on the form component.
        'disk' => env('LIVEWIRE_UPLOAD_DISK', 'local'),
        'rules' => ['required', 'file', 'max:819200'],
        'directory' => 'livewire-tmp',
        'middleware' => 'throttle:120,10',
        'preview_mimes' => ['png', 'gif', 'bmp', 'svg', 'wav', 'mp4', 'mov', 'avi', 'wmv', 'mp3', 'm4a', 'jpg', 'jpeg', 'mpga', 'webp', 'wma'],
        'max_upload_time' => 3600,
        'cleanup' => true,
    ],
];
