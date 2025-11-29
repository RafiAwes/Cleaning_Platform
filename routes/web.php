<?php

use App\Jobs\TestQueueJob;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GoogleController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-queue', function () {
    TestQueueJob::dispatch();
    return "Job dispatched";
});

Route::controller(GoogleController::class)->group(function () {
    Route::get('auth/google/redirect', 'redirectToGoogle')->middleware('web');
    Route::get('auth/google/callback', 'handleGoogleCallback')->middleware('web');
});

Route::get('/test-google-config', function () {
    return response()->json([
        'app_url' => config('app.url'),
        'client_id' => config('services.google.client_id'),
        'redirect_uri' => config('services.google.redirect'),
        'has_client_id' => !empty(config('services.google.client_id')),
        'has_client_secret' => !empty(config('services.google.client_secret')),
        'session_driver' => config('session.driver'),
        'session_domain' => config('session.domain'),
    ]);
});

Route::get('/test-session', function () {
    session()->put('test_key', 'test_value');
    return response()->json([
        'session_id' => session()->getId(),
        'session_data' => session()->all(),
        'has_test_key' => session()->has('test_key'),
    ]);
});

Route::get('/test-session-save', function () {
    // Test session saving
    session()->put('test_save', time());
    session()->save();
    
    return response()->json([
        'message' => 'Session saved',
        'session_id' => session()->getId(),
        'session_data' => session()->all(),
    ]);
});