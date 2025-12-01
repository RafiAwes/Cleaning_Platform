<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AddonController;
use App\Http\Controllers\Api\GoogleController;
use App\Http\Controllers\Api\StripeController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Vendor\VendorController;
use App\Http\Controllers\Api\Customer\CustomerController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;


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
Route::post('/stripe/webhook', [StripeController::class, 'webhook']);

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

//availability date
Route::get('/availability-date/{$packageId}', [BookingController::class, 'getAvailabilityDate'])->name('availability.date');

// Protected routes requiring authentication
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);  
    
    // stripe
    Route::post('/stripe/connect', [StripeController::class, 'connectStripe']);
    Route::get('/stripe/callback', [StripeController::class, 'callback']);
    Route::post('/stripe/create-payment-intent', [StripeController::class, 'createPaymentIntent']);
    Route::post('/delivery/confirm', [StripeController::class, 'confirmDelivery']);

    // Customer routes
    Route::group(['prefix' => 'customer', 'middleware' => 'role:customer'], function () {
        Route::get('/dashboard', [CustomerController::class, 'dashboard']);
        Route::post('/profile/update', [CustomerController::class, 'updateProfile']);
        Route::group(['controller' => BookingController::class], function () {
            Route::post('create/bookings', 'createBooking')->name('create.booking');
            Route::post('cancel/bookings', 'cancelBooking')->name('cancel.booking');
            Route::get('bookings', 'getBookings')->name('get.bookings');
            Route::post('/ratings', 'rateBooking')->name('rate.booking');
            
        });
    });
    
    // Vendor routes
    Route::group(['prefix' => 'vendor', 'middleware' => 'role:vendor'], function () {
        Route::group(['controller' => VendorController::class], function () {
            Route::get('/dashboard', 'dashboard');
            Route::post('/profile/update', 'updateOrCreate');
            Route::post('/address/update', 'updateAddress');
        
            // Packages
            Route::get('/packages', 'packages')->name('vendor.packages');
            Route::post('/packages', 'createPackage');
            Route::put('/packages/{package}', 'updatePackage');
            Route::delete('/packages/{package}', 'deletePackage');

            //cleaner
            Route::post('/cleaners', 'addCleaner')->name('add.cleaner');
            Route::get('/cleaners', 'getCleaners')->name('get.cleaners');

            //target
            Route::post('/booking/targets', 'bookingTarget')->name('add.target');
            Route::get('/revenue/targets', 'revenueTargets')->name('get.targets');
            Route::get('/targets', 'getTargets')->name('get.targets');
            Route::get('/total', 'totalEarnings')->name('get.total');
            Route::get('/transaction/history', 'transactionHistory')->name('get.transaction.history');
        });
        
        // Services
        // Route::apiResource('services', ServiceController::class);
        
        // Addons
        Route::apiResource('addons', AddonController::class);
        
        // Bookings
        Route::group(['controller' => BookingController::class], function () {
            Route::get('/bookings', 'vendorBookings');
            Route::get('/bookings', 'getBookings');
            Route::get('/booking-details/{bookingId}', 'getBookingDetails')->name('booking.details');
            Route::post('/booking/accept/{bookingId}', 'acceptBooking')->name('booking.accept');
            Route::post('/booking/reject/{bookingId}', 'rejectBooking')->name('booking.reject');
            Route::post('/booking/complete/{bookingId}', 'completeBooking')->name('booking.complete');
            Route::post('/cancel/bookings/{bookingId}', 'cancelBooking')->name('booking.cancel');
        });
        
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
});