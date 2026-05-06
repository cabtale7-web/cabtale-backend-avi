<?php

use Illuminate\Support\Facades\Route;

Route::get('/test-otp-config', function () {
    return response()->json([
        'APP_MODE' => env('APP_MODE'),
        'config_app_mode' => config('app.mode'),
        'test_otp' => env('APP_MODE') != "live" ? '0000' : rand(1000, 9999),
    ]);
});
