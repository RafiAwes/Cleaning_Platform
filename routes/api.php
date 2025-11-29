<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Vendor\VendorController;
use App\Http\Controllers\Api\Customer\CustomerController;
use App\Http\Controllers\Api\GoogleController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\AddonController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/register/customer', [AuthController::class, 'registerCustomer']);
Route::post('/register/vendor', [AuthController::class, 'registerVendor']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/admin/login', [AuthController::class, 'adminLogin']);

// Google OAuth routes with web middleware for session support
Route::group(['controller' => GoogleController::class, 'middleware' => 'web'], function () {
    Route::get('/auth/google/redirect/customer', 'redirectToGoogle')->name('google.redirect.customer');
    Route::get('/auth/google/redirect/vendor', 'redirectToGoogle')->name('google.redirect.vendor');
    Route::get('/auth/google/callback', 'handleGoogleCallback')->name('google.callback');
});

// Email Verification Routes
Route::post('/email/send-verification-code', [EmailVerificationController::class, 'sendVerificationCode']);
Route::post('/email/verify', [EmailVerificationController::class, 'verifyEmail']);

// Password Reset Routes
Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('/password/reset', [ForgotPasswordController::class, 'resetPassword']);

// Protected routes requiring authentication
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);  
    
    // Customer routes
    Route::group(['prefix' => 'customer', 'middleware' => 'role:customer'], function () {
        Route::get('/dashboard', [CustomerController::class, 'dashboard']);
        Route::post('/profile/update', [CustomerController::class, 'updateProfile']);
    });
    
    // Vendor routes
    Route::group(['prefix' => 'vendor', 'middleware' => 'role:vendor'], function () {
        Route::get('/dashboard', [VendorController::class, 'dashboard']);
        Route::post('/profile/update', [VendorController::class, 'updateOrCreate']);
        Route::post('/address/update', [VendorController::class, 'updateAddress']);
        
        // Packages
        Route::get('/packages', [VendorController::class, 'packages']);
        Route::post('/packages', [VendorController::class, 'createPackage']);
        Route::put('/packages/{package}', [VendorController::class, 'updatePackage']);
        Route::delete('/packages/{package}', [VendorController::class, 'deletePackage']);
        
        // Services
        // Route::apiResource('services', ServiceController::class);
        
        // Addons
        Route::apiResource('addons', AddonController::class);
        
        // Bookings
        Route::get('/bookings', [BookingController::class, 'vendorBookings']);
        Route::put('/bookings/{booking}/status', [BookingController::class, 'updateBookingStatus']);
        
        // Cleaners
        Route::post('/cleaners', [VendorController::class, 'addCleaner']);
        Route::get('/cleaners', [VendorController::class, 'getCleaners']);
        
        // Transactions
        // Route::get('/transactions', [TransactionController::class, 'vendorTransactions']);
        
        // Inventory
        Route::apiResource('inventory', InventoryController::class);
        
        // Booking targets
        Route::post('/booking-target', [VendorController::class, 'bookingTarget']);
    });
    
    // Admin routes
    Route::group(['prefix' => 'admin', 'middleware' => 'role:admin'], function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/vendors/pending', [AdminController::class, 'getPendingVendors']);
        Route::post('/vendors/{vendorId}/approve', [AdminController::class, 'approveVendor']);
        Route::post('/vendors/{vendorId}/reject', [AdminController::class, 'rejectVendor']);
        Route::get('/vendors', [AdminController::class, 'getAllVendors']);
        Route::get('/customers', [AdminController::class, 'getAllCustomers']);
    });
    
    // Common routes for all authenticated users
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::delete('/bookings/{booking}', [BookingController::class, 'destroy']);
    
    // Route::apiResource('reviews', ReviewController::class)->only(['store', 'index']);
    
    // Route::post('/payment/process', [PaymentController::class, 'processPayment']);
    // Route::get('/payment/history', [PaymentController::class, 'paymentHistory']);
    
    // Route::get('/notifications', [NotificationController::class, 'index']);
    // Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
});