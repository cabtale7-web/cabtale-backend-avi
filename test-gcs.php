<?php
// test-gcs.php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;

try {
    // Get Laravel's FilesystemAdapter, then call put() (Laravel wraps Flysystem v3)
    $disk = Storage::disk('gcs');
    
    // Laravel v10+ uses put() on the adapter
    $disk->put('test-healthcheck.txt', 'GCS is working!');

    echo "GCS storage connection successful!";
} catch (\Exception $e) {
    echo "GCS storage connection failed: " . $e->getMessage();
}