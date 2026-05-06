<?php
// Access via: http://35.200.151.140/fix_otp_cache.php

echo "<h2>Fixing OTP Cache Issue</h2>";
echo "<pre>";

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Delete config cache
$configCache = __DIR__ . '/bootstrap/cache/config.php';
if (file_exists($configCache)) {
    if (unlink($configCache)) {
        echo "✓ Deleted config cache file\n";
    } else {
        echo "✗ Failed to delete config cache (check permissions)\n";
    }
} else {
    echo "✓ Config cache file doesn't exist\n";
}

// Clear Laravel caches
try {
    Artisan::call('config:clear');
    echo "✓ Config cleared\n";
    
    Artisan::call('cache:clear');
    echo "✓ Cache cleared\n";
    
    Artisan::call('config:cache');
    echo "✓ Config cached\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test again
echo "\n--- Testing After Fix ---\n";
echo "env('APP_MODE'): " . env('APP_MODE') . "\n";

$env = env('APP_MODE');
$testOtp = $env != "live" ? '0000' : rand(1000, 9999);
echo "Test OTP: $testOtp\n";

if ($testOtp != '0000') {
    echo "\n✓✓✓ SUCCESS! OTP is now working correctly! ✓✓✓\n";
} else {
    echo "\n✗✗✗ Still not working. Manual intervention needed. ✗✗✗\n";
}

echo "\nNow restart your web server:\n";
echo "sudo systemctl restart php8.2-fpm\n";
echo "sudo systemctl restart nginx\n";

echo "</pre>";
