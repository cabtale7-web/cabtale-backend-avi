<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Test OTP Configuration
Route::get('/test-otp-config', function () {
    return response()->json([
        'APP_MODE' => env('APP_MODE'),
        'is_live' => env('APP_MODE') === 'live',
        'test_otp_1' => env('APP_MODE') != "live" ? '0000' : rand(1000, 9999),
        'test_otp_2' => env('APP_MODE') != "live" ? '0000' : rand(1000, 9999),
        'test_otp_3' => env('APP_MODE') != "live" ? '0000' : rand(1000, 9999),
    ]);
});
