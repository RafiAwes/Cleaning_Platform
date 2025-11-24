<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;




// Public routes for authentication
// Route::post('/register', [AuthController::class, 'register']);
// Route::post('/login', [AuthController::class, 'login']);


Route::prefix('user')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('user.login');
    Route::post('register', [AuthController::class, 'register'])->name('user.register');
})->middleware('guest');

// Protected routes requiring authentication
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
});
