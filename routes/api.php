<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AddonController;
use App\Http\Controllers\Api\CleanerController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Vendor\VendorController;
use App\Http\Controllers\Api\Customer\CustomerController;


Route::post('/register', [AuthController::class, 'register']);
// Public routes for authentication
Route::post('/login', [AuthController::class, 'login']);

// Protected routes requiring authentication
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);  
});

// Admin routes
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('admin/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/addons', [AddonController::class, 'getAddons']);
    Route::post('/addon/create', [AddonController::class, 'createAddon']);
    Route::put('/addon/{addon}', [AddonController::class, 'updateAddon']);
    Route::delete('/addon/{addon}', [AddonController::class, 'removeAddon']);
});

// Vendor routes
Route::middleware(['auth:sanctum', 'role:vendor'])->group(function () {
    Route::group(['controller' => VendorController::class], function () {
        Route::get('vendor/dashboard', 'dashboard');
        Route::put('vendor/profile', 'update');
        Route::get('vendor/packages', 'packages');
        Route::post('vendor/package/create', 'CreatePackage');
        Route::put('vendor/package/{package}', 'updatePackage');
        Route::delete('vendor/package/{package}', 'deletePackage');
        Route::post('vendor/add/cleaner', 'addCleaner');
    });
    Route::group(['controller' => CleanerController::class], function () {
        Route::post('vendor/add/cleaner', 'create');
        Route::put('vendor/cleaner/{cleaner}', 'update');
        Route::delete('vendor/cleaner/{cleaner}', 'delete');
        Route::post('vendor/bookings/{booking_id}/assign-cleaner','assignCleaners');
    });
    Route::group(['controller' => BookingController::class], function () {
        Route::get('vendor/bookings', 'index');
        Route::put('vendor/booking/{booking}', 'update');
        Route::delete('vendor/booking/{booking}', 'delete');
        Route::post('vendor/accept/booking/{booking}', 'acceptBooking');
        Route::post('vendor/reject/booking/{booking}', 'rejectBooking');
    });
});

// Customer routes
Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {
    Route::get('customer/dashboard', [CustomerController::class, 'dashboard']);
});