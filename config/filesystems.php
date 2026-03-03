<?php

return [

    'default' => env('FILESYSTEM_DRIVER', 'gcs'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
        ],

        'gcs' => [
            'driver' => 'gcs',
            'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
            'key_file_path' => env('GOOGLE_APPLICATION_CREDENTIALS'), // absolute path to JSON
            'bucket' => env('GOOGLE_CLOUD_STORAGE_BUCKET'),
            'path_prefix' => null,
            'storage_api_uri' => null,
            'options' => [
                'predefinedAcl' => null, // MUST be null for UBLA (uniform bucket-level access)
            ],
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];