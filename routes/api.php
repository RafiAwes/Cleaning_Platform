<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Vendor\VendorController;
use App\Http\Controllers\Api\Customer\CustomerController;




// Public routes for authentication
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes requiring authentication
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('admin/dashboard',[AdminController::class, 'dashboard']);
});

Route::middleware(['auth:sanctum', 'role:vendor'])->group(function () {
    Route::get('vendor/dashboard',[VendorController::class, 'dashboard']);
});

Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {
    Route::get('customer/dashboard',[CustomerController::class, 'dashboard']);
});

