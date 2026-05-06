<?php
// Run this file directly to check OTP configuration
// Access via: http://35.200.151.140/check_otp_issue.php

echo "<h2>OTP Configuration Check</h2>";
echo "<pre>";

// Check .env file
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    preg_match('/APP_MODE=(.*)/', $envContent, $matches);
    echo "1. .env file APP_MODE: " . ($matches[1] ?? 'NOT FOUND') . "\n";
} else {
    echo "1. .env file: NOT FOUND\n";
}

// Check if Laravel is loaded
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "2. env('APP_MODE'): " . env('APP_MODE') . "\n";
echo "3. config('app.mode'): " . config('app.mode', 'NOT SET') . "\n";

// Test OTP generation
$env = env('APP_MODE');
$otp1 = $env != "live" ? '0000' : rand(1000, 9999);
$otp2 = $env != "live" ? '0000' : rand(1000, 9999);
$otp3 = $env != "live" ? '0000' : rand(1000, 9999);

echo "\n4. Test OTP Generation:\n";
echo "   - OTP 1: $otp1\n";
echo "   - OTP 2: $otp2\n";
echo "   - OTP 3: $otp3\n";

echo "\n5. Comparison Check:\n";
echo "   - env('APP_MODE') == 'live': " . (env('APP_MODE') == 'live' ? 'TRUE' : 'FALSE') . "\n";
echo "   - env('APP_MODE') === 'live': " . (env('APP_MODE') === 'live' ? 'TRUE' : 'FALSE') . "\n";
echo "   - env('APP_MODE') != 'live': " . (env('APP_MODE') != 'live' ? 'TRUE' : 'FALSE') . "\n";

echo "\n6. Check for hidden characters:\n";
echo "   - Length: " . strlen(env('APP_MODE')) . "\n";
echo "   - Hex: " . bin2hex(env('APP_MODE')) . "\n";

echo "\n7. Config cache file:\n";
$configCache = __DIR__ . '/bootstrap/cache/config.php';
if (file_exists($configCache)) {
    echo "   - EXISTS (This might be the problem!)\n";
    echo "   - Delete this file and try again\n";
} else {
    echo "   - NOT FOUND (Good)\n";
}

echo "</pre>";
