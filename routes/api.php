<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AddonController;
use App\Http\Controllers\Api\StripeController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CleanerController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\InventoryController;
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

Route::get('/packages/{id}/availability', [BookingController::class, 'checkAvailabilityByDate'])->name('checkAvailability');

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
        Route::post('set/revenue/target', 'setRevenueTarget');
        Route::post('set/booking/target', 'setBookingTarget');
        Route::get('/vendor/transactions', 'transactionHistory');
        Route::get('/vendor/transactions/total', 'totalEarnings')->name('totalEarnings');
    });
    Route::group(['controller' => CleanerController::class], function () {
        Route::post('vendor/add/cleaner', 'create')->name('createCleaner');
        Route::put('vendor/update/cleaner/{cleaner}', 'update')->name('updateCleaner');
        Route::delete('vendor/remove/cleaner/{cleaner}', 'delete')->name('deleteCleaner');
        Route::post('vendor/bookings/{booking_id}/assign-cleaner','assignCleaners')->name('assignCleaners');
    });
    Route::group(['controller' => BookingController::class], function () {
        // Route::get('vendor/bookings', 'index');
        Route::put('vendor/booking/{booking}', 'update');
        Route::delete('vendor/booking/{booking}', 'delete');
        Route::post('vendor/accept/booking/{booking}', 'acceptBooking');
        Route::post('vendor/reject/booking/{booking}', 'rejectBooking');
        Route::post('vendor/complete/booking/{booking}', 'completeBooking')->name('completeBooking');
    });
    Route::group(['controller' => InventoryController::class], function () {
        Route::post('vendor/add/product', 'addProduct')->name('addProduct');
        Route::put('vendor/update/product/{product}', 'updateProduct')->name('updateProduct');
        Route::delete('vendor/remove/product/{product}', 'deleteProduct')->name('deleteProduct');
        Route::get('vendor/products', 'getProductsByVendor')->name('getProducts');
        Route::get('vendor/product/details/{product_id}', 'productDetails')->name('getProductDetails');
    });
});

// Customer routes
Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {
    Route::group(['controller' => CustomerController::class], function () {
        Route::get('customer/dashboard', 'dashboard');
        Route::post('customer/accept/delivery/{package_id}', 'acceptDelivery');
        Route::post('customer/reject/delivery/{package_id}', 'rejectDelivery');
    });
    Route::group(['controller' => BookingController::class], function () {
        Route::get('customer/bookings', 'index');
        Route::post('customer/create/booking', 'createBooking');
        Route::put('customer/bookings/{booking}', 'update');
        Route::delete('customer/bookings/{booking}', 'delete');
        Route::get('/packages/{id}/bookings', 'getBookings')->name('getBookings');
        Route::post('/packages/{id}/bookings', 'createBooking')->name('createBooking');
    });
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('customer/dashboard', [CustomerController::class, 'dashboard']);
    Route::group(['controller' => BookingController::class], function () {
        Route::get('/packages/{id}/availability', 'checkAvailabilityByDate')->name('checkAvailability');
        Route::get('/packages/{id}/bookings', 'getBookings')->name('getBookings');
        Route::post('/packages/{id}/bookings', 'createBooking')->name('createBooking');
        Route::post('/packages/{id}/cancel-booking', 'cancelBooking')->name('cancelBooking');
    });
    Route::group(['controller' => StripeController::class], function () {
        Route::get('/vendor/stripe/connect', 'connectStripe');
        Route::get('/stripe/callback', 'callback');
        Route::post('/payment-intent', 'createPaymentIntent');
    });

    Route::get('/notifications', function () {
        $notifications = Auth::user()->notifications;
        return response()->json(['notifications' => $notifications], 200);
    });
    Route::post('/notifications/read-all', function () {
        Auth::user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'Notifications marked as read'], 200);
    });
    Route::post('/notifications/{id}/read', function ($id){
        $notification = Auth::user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
            return response()->json(['message' => 'Notification marked as read'], 200);
        } else {
            return response()->json(['message' => 'Notification not found'], 404);
        }
    });
    Route::group(['controller' => MessageController::class], function () {
        Route::post('/messages/send', 'sendMessage');
        Route::get('/messages/{userId}', 'getMessages');
    });
    Route::group(['controller' => CleanerController::class], function () {
        Route::post('vendor/add/cleaner', 'create')->name('createCleaner');
        Route::put('vendor/update/cleaner/{cleaner}', 'update')->name('updateCleaner');
        Route::delete('vendor/remove/cleaner/{cleaner}', 'delete')->name('deleteCleaner');
        Route::post('vendor/bookings/{booking_id}/assign-cleaner','assignCleaners')->name('assignCleaners');
    });             
});