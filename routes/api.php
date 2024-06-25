<?php

use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartItemController;
use App\Http\Controllers\Api\V1\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('/products', ProductController::class);

    // Protected routes requiring authentication
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        Route::get('/cart', [CartItemController::class, 'index']);
        Route::post('/cart_item', [CartItemController::class, 'store']);
        Route::delete('/cart/{id}', [CartItemController::class, 'destroy']);

        Route::post('/order', [OrderController::class, 'placeOrder']);
        Route::get('/orders', [OrderController::class, 'index']);
        
        Route::get('/current-authentication', [AuthController::class, 'current_authentication']);
        
    });
});


