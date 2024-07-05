<?php

use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartItemController;
use App\Http\Controllers\Api\v1\DownloadFileController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/register', [UserController::class, 'register']);
    Route::get('users', [UserController::class, 'index']);
    Route::delete('/user/{id}', [UserController::class, 'destroy']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/search', [ProductController::class, 'search']);
    Route::get('/products/featured', [ProductController::class, 'featured']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::post('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // Protected routes requiring authentication
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        Route::get('/download/file', [DownloadFileController::class, 'downloadImage']);

        Route::post('update-user/{id}', [UserController::class, 'updateProfile']);
        Route::post('change-password/{id}', [UserController::class, 'changePassword']);

        Route::get('/cart', [CartItemController::class, 'index']);
        Route::post('/cart', [CartItemController::class, 'store']);
        Route::put('/cart/{id}', [CartItemController::class, 'update']);
        Route::delete('/cart/{id}', [CartItemController::class, 'destroy']);

        Route::post('/orders', [OrderController::class, 'placeOrder']);
        Route::get('/orders', [OrderController::class, 'index']);

        Route::get('/current-authentication', [AuthController::class, 'current_authentication']);
    });
});
