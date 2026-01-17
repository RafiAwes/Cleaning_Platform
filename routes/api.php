<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Auth\{AuthController, EmailVerificationController, ForgotPasswordController};
use App\Http\Controllers\Api\Customer\CustomerController;
use App\Http\Controllers\Api\{AddonController, BlogController, BookingController, CategoryController, CleanerController, GoogleController, InventoryController, PageController, ServiceController, StripeController};
use App\Http\Controllers\Api\Vendor\{PackageController, VendorController};


// Public routes
Route::post('/register/', [AuthController::class, 'register']);
// Route::post('/register/vendor', [AuthController::class, 'registerVendor']);
Route::post('/login', [AuthController::class, 'login']);
// Route::post('/admin/login', [AuthController::class, 'adminLogin']);
Route::post('/stripe/webhook', [StripeController::class, 'webhook']);
// Route::post('/categories', [categoryController::class, 'createCategory']);
Route::get('/page/contents', [PageController::class, 'indexPageContent']);
Route::get('/faq/contents', [PageController::class, 'indexFaqContent']);
Route::GET('/blogs', [BlogController::class, 'index'])->name('list.blogs');
Route::GET('/categories', [CategoryController::class, 'categoryListPublic']);

// Public packages/services endpoint
Route::get('/packages', [PackageController::class, 'getAllPackagesPublic'])->name('packages.public');
Route::get('/packages/{id}', [PackageController::class, 'getPackagePublic'])->name('package.public.show');
Route::get('/packages/vendor/{vendorId}', [PackageController::class, 'getVendorPackages'])->name('packages.vendor');
Route::get('/packages-random/suggestions', [PackageController::class, 'getRandomPackages'])->name('packages.random');

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
Route::get('/availability-date/{packageId}', [BookingController::class, 'getAvailabilityDate'])->name('availability.date');

// Protected routes requiring authentication
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
<<<<<<< HEAD
    Route::post('/logout', [AuthController::class, 'logout']);

    // Addons - accessible to all authenticated users
    Route::get('/addons', [AddonController::class, 'getAddons'])->name('get.addons');

=======
    Route::post('/logout', [AuthController::class, 'logout']);  
    
    // Addons - accessible to all authenticated users
    Route::get('/addons', [AddonController::class, 'getAddons'])->name('get.addons');
    
>>>>>>> 0e957735c0968fac7bab88b1465322d09bf19d6f
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
            Route::post('/add/custom/booking', 'addCustomBooking')->name('add.custom.booking');
            Route::post('get/custom/booking', 'getCustomBooking')->name('get.custom.booking');

        });
    });

    // Vendor routes
    Route::group(['prefix' => 'vendor', 'middleware' => 'role:vendor'], function () {
        Route::group(['controller' => VendorController::class], function () {

            Route::post('/upload-business-documents', 'uploadBusinessDocuments');
            Route::get('/document-status', 'getDocumentStatus');
            Route::get('/dashboard', 'dashboard');
            Route::post('/profile/update', 'updateOrCreate');
            Route::post('/address/update', 'updateAddress');

            //cleaner
            Route::post('/cleaners', 'addCleaner')->name('add.cleaner');
            Route::get('/cleaners', 'getCleaners')->name('get.cleaners');
            Route::get('/cleaners/{cleaner}', 'getCleaner')->name('get.cleaner');
            Route::put('/cleaners/{cleaner}', 'updateCleaner')->name('update.cleaner');
            Route::delete('/cleaners/{cleaner}', 'deleteCleaner')->name('delete.cleaner');

            //target
            Route::post('/booking/targets', 'bookingTarget')->name('add.target');
            Route::get('/revenue/targets', 'revenueTargets')->name('get.targets');
            Route::get('/targets', 'getTargets')->name('get.targets');
            Route::get('/total', 'totalEarnings')->name('get.total');
            Route::get('/transaction/history', 'transactionHistory')->name('get.transaction.history');
        });

        Route::group(['controller' => PackageController::class], function () {
            // Packages
            Route::get('/packages', 'packages')->name('vendor.packages');
            Route::post('/package/create', 'createPackage');
            Route::put('/package/{package}', 'updatePackage');
            Route::delete('/packages/{package}', 'deletePackage');
        });

        Route::group(['controller' => InventoryController::class], function () {
            // Inventory
            Route::post('/inventory', 'vendorAddProduct')->name('vendor.add.product');
            Route::get('/inventory', 'vendorGetProducts')->name('vendor.get.products');
            Route::get('/inventory/{inventory}', 'vendorGetProduct')->name('vendor.get.product');
            Route::post('/inventory/{inventory}', 'vendorUpdateProduct')->name('vendor.update.product');
            Route::delete('/inventory/{inventory}', 'vendorDeleteProduct')->name('vendor.delete.product');
        });
<<<<<<< HEAD

        // Services
        // Route::apiResource('services', ServiceController::class);

            // Addons
            // Route::apiResource('addons', AddonController::class); // Moved outside vendor group to allow broader access

=======
        
        // Services
        // Route::apiResource('services', ServiceController::class);
        
            // Addons
            // Route::apiResource('addons', AddonController::class); // Moved outside vendor group to allow broader access
        
>>>>>>> 0e957735c0968fac7bab88b1465322d09bf19d6f
        // Bookings
        Route::group(['controller' => BookingController::class], function () {
            Route::get('/bookings', 'vendorBookings');
            Route::get('/booking-details/{bookingId}', 'getBookingDetails')->name('booking.details');
            Route::post('/booking/accept/{bookingId}', 'acceptBooking')->name('booking.accept');
            Route::post('/booking/reject/{bookingId}', 'rejectBooking')->name('booking.reject');
            Route::post('/booking/complete/{bookingId}', 'completeBooking')->name('booking.complete');
            Route::post('/cancel/bookings/{bookingId}', 'cancelBooking')->name('booking.cancel');
        });

        // custom service
        Route::group(['controller' => ServiceController::class], function () {
            Route::post('/create/custom-service', 'createCustomPrice')->name('custom.service.create');
        });

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
        Route::get('/categories', [CategoryController::class, 'categoryList']);
        Route::group(['controller' => CategoryController::class], function () {
            Route::post('/edit/category/{category_id}', 'editCategory');
            Route::post('/add/category', 'createCategory');
            Route::post('/delete/category/{category_id}', 'deleteCategory');
        });
        Route::group(['controller' => AddonController::class], function () {
            Route::post('/addons', 'createAddon');
            Route::put('/addons/{addon}', 'updateAddon');
            Route::delete('/addons/{addon}', 'deleteAddon');
        });

        Route::POST('/add/page/content', [PageController::class, 'createPageContent'])->name('add.page.content');
        Route::POST('/add/faq/content', [PageController::class, 'createFaqContent'])->name('add.faq.content');
        Route::group(['controller' => BlogController::class], function () {
            Route::POST('/blog', 'createBlog')->name('create.blog');
            Route::PUT('/blog/{id}', 'updateBlog')->name('update.blog');
            Route::DELETE('/blog/{id}', 'deleteBlog')->name('delete.blog');
        });

        Route::group(['controller' => CategoryController::class], function () {
            Route::post('/create/category', 'createCategory');
            Route::put('/category/{id}', 'editCategory');
            Route::delete('/category/{id}', 'deleteCategory');
            Route::GET('/category', 'categoryList');
        });

    });
});

