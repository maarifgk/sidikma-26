<?php

return [
    'temporary_file_upload' => [
        // Temporary Livewire files must use the normal private local disk.
        // The final template is moved to the dedicated private disk by the
        // form component after the request is submitted. Using the served
        // final disk here can make Livewire preview/signature requests fail
        // with 403 on private filesystems.
        'disk' => 'local',
        'rules' => ['required', 'file', 'max:819200'],
        'directory' => 'livewire-tmp',
        'middleware' => 'throttle:120,10',
        'preview_mimes' => ['png', 'gif', 'bmp', 'svg', 'wav', 'mp4', 'mov', 'avi', 'wmv', 'mp3', 'm4a', 'jpg', 'jpeg', 'mpga', 'webp', 'wma'],
        'max_upload_time' => 3600,
        'cleanup' => true,
    ],
];
