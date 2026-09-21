<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'documents' => [
            'driver' => 'local',
            'root' => storage_path('app/private/documents'),
            'serve' => false,
            'visibility' => 'private',
            'throw' => false,
            'report' => false,
        ],

        'attendance' => [
            'driver' => 'local',
            'root' => storage_path('app/private/attendance'),
            'serve' => false,
            'visibility' => 'private',
            'throw' => false,
            'report' => false,
        ],

        'complaints' => [
            'driver' => 'local',
            'root' => storage_path('app/private/complaints'),
            'serve' => false,
            'visibility' => 'private',
            'throw' => false,
            'report' => false,
        ],

        'decree-proposals' => [
            'driver' => 'local',
            'root' => storage_path('app/private/decree-proposals'),
            'serve' => true,
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/decree-proposal-files',
            'visibility' => 'private',
            'throw' => false,
            'report' => false,
        ],

        'sipinter-updates' => [
            'driver' => 'local',
            'root' => storage_path('app/private/sipinter-updates'),
            'serve' => true,
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/sipinter-files',
            'visibility' => 'private',
            'throw' => false,
            'report' => false,
        ],

        'employee-mutations' => [
            'driver' => 'local',
            'root' => storage_path('app/private/employee-mutations'),
            'serve' => true,
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/mutation-files',
            'visibility' => 'private',
            'throw' => false,
            'report' => false,
        ],

        'employee-activities' => [
            'driver' => 'local',
            'root' => storage_path('app/private/employee-activities'),
            'serve' => true,
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/activity-request-files',
            'visibility' => 'private',
            'throw' => false,
            'report' => false,
        ],

        'correspondence-requests' => [
            'driver' => 'local',
            'root' => storage_path('app/private/correspondence-requests'),
            'serve' => true,
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/correspondence-files',
            'visibility' => 'private',
            'throw' => false,
            'report' => false,
        ],

        'proposal-requests' => [
            'driver' => 'local',
            'root' => storage_path('app/private/proposal-requests'),
            'serve' => true,
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/proposal-request-files',
            'visibility' => 'private',
            'throw' => false,
            'report' => false,
        ],

        'foundation-annual-reports' => [
            'driver' => 'local',
            'root' => storage_path('app/private/foundation-annual-reports'),
            'serve' => true,
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/foundation-annual-report-files',
            'visibility' => 'private',
            'throw' => false,
            'report' => false,
        ],

        'treasury-transactions' => [
            'driver' => 'local',
            'root' => storage_path('app/private/treasury-transactions'),
            'serve' => true,
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/treasury-transaction-files',
            'visibility' => 'private',
            'throw' => false,
            'report' => false,
        ],

        'learning-modules' => [
            'driver' => 'local',
            'root' => storage_path('app/private/learning-modules'),
            'serve' => true,
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/learning-module-files',
            'visibility' => 'private',
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            // Relative URL keeps uploaded images working when the application is
            // opened from localhost, 127.0.0.1, a LAN IP, or a production domain.
            'url' => '/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
