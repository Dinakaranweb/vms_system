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

        /*
        |----------------------------------------------------------------------
        | The "public" disk — local or S3, chosen by env alone
        |----------------------------------------------------------------------
        |
        | Every upload in this application is written through Storage::disk('public')
        | (25 call sites: tender documents, vendor proposals, postal scans, received
        | documents). Rather than change those call sites, the disk NAME stays "public"
        | and only its DRIVER moves. Set PUBLIC_DISK_DRIVER=s3 in .env and every one of
        | those writes goes to the bucket; set it back to "local" and they return to
        | storage/app/public. No application code knows the difference.
        |
        | The two branches are kept separate on purpose: "root" means a filesystem path
        | to the local driver but a key prefix to S3, so sharing one value between them
        | would write an absolute Windows path into every object key.
        |
        */
        'public' => env('PUBLIC_DISK_DRIVER', 'local') === 's3'
            ? [
                'driver' => 's3',
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
                'region' => env('AWS_DEFAULT_REGION'),
                'bucket' => env('AWS_BUCKET'),
                // ?: null matters. An empty "AWS_URL=" line in .env gives "" rather than null,
                // and isset("") is true, so Laravel would treat it as an explicit base URL and
                // return "/key" instead of the bucket URL. Same hazard for the endpoint.
                'url' => env('AWS_URL') ?: null,
                'endpoint' => env('AWS_ENDPOINT') ?: null,
                'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
                // Optional key prefix inside the bucket, e.g. "vms" → vms/tender-documents/...
                'root' => env('AWS_ROOT', ''),
                'visibility' => 'public',
                'throw' => false,
                'report' => false,
            ]
            : [
                'driver' => 'local',
                'root' => storage_path('app/public'),
                'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
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
            'url' => env('AWS_URL') ?: null,
            'endpoint' => env('AWS_ENDPOINT') ?: null,
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'root' => env('AWS_ROOT', ''),
            'visibility' => 'public',
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
