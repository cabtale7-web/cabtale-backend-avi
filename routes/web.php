<?php

use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\PaymentRecordController;
use App\Http\Controllers\TestMailController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Modules\TripManagement\Entities\TripRequest;
use Pusher\Pusher;
use Pusher\PusherException;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/sender', function(){
    return event(new App\Events\NewMessage("hello"));
});

Route::controller(LandingPageController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/contact-us', 'contactUs')->name('contact-us');
    Route::get('/about-us', 'aboutUs')->name('about-us');
    Route::get('/privacy', 'privacy')->name('privacy');
    Route::get('/terms', 'terms')->name('terms');
    Route::get('/test-connection', function (){
        $trip = TripRequest::first();
        try {
            checkPusherConnection(\App\Events\CustomerTripRequestEvent::broadcast($trip->driver,$trip));
        } catch(Exception $exception) {
            // handle exception
        }
    });
});

Route::get('/update-data-test', [\App\Http\Controllers\DemoController::class,'demo'])->name('demo');
Route::get('/test-mail', [TestMailController::class, 'sendTest']);
Route::get('add-payment-request', [PaymentRecordController::class, 'index']);

Route::get('payment-success', [PaymentRecordController::class, 'success'])->name('payment-success');
Route::get('payment-fail', [PaymentRecordController::class, 'fail'])->name('payment-fail');
Route::get('payment-cancel', [PaymentRecordController::class, 'cancel'])->name('payment-cancel');
Route::get('sms-test', [\App\Http\Controllers\DemoController::class, 'smsGatewayTest'])->name('sms-test');
Route::get('users', [\App\Http\Controllers\DemoController::class, 'updateUnRefCodeUsers'])->name('users');


// -------------------- GCS TEST ROUTE --------------------
Route::get('/gcs-test', function () {
    $disk = Storage::disk('gcs');

    // Upload a test file
    try {
        $disk->put('healthcheck.txt', 'GCS is working!');
    } catch (\League\Flysystem\UnableToWriteFile $e) {
        return "Write failed: " . $e->getMessage();
    }

    // Read the file
    try {
        $content = $disk->get('healthcheck.txt');
    } catch (\League\Flysystem\UnableToReadFile $e) {
        return "Read failed: " . $e->getMessage();
    }

    return $content; // Expected: "GCS is working!"
});