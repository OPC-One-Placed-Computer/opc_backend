<?php

use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartItemController;
use App\Http\Controllers\Api\V1\DownloadFileController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrdersController;
use App\Http\Controllers\Api\V1\OrderStatusController;
use App\Http\Controllers\Api\V1\UpdateProfileController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/register', [UserController::class, 'register']);

    Route::get('/products', [ProductsController::class, 'index']);
    Route::get('/products/{id}', [ProductsController::class, 'show']);
    Route::get('/products/featured', [ProductsController::class, 'featured']);

    Route::post('/webhook/stripe', [PaymentController::class, 'handleWebhook']);

    Route::get('stripe/status', [PaymentController::class, 'stripeOrderStatus']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        Route::get('/current/authentication', [AuthController::class, 'current_authentication']);
        Route::get('/download/file', [DownloadFileController::class, 'downloadImage']);

        Route::middleware('role:admin')->group(function () {
            Route::get('users', [UserController::class, 'index']);
            Route::delete('/user/{id}', [UserController::class, 'destroy']);

            Route::post('/products', [ProductController::class, 'store']);
            Route::post('/products/{id}', [ProductController::class, 'update']);
            Route::delete('/products/{id}', [ProductController::class, 'destroy']);

            Route::get('/orders/all', [OrdersController::class, 'allOrders']);

            Route::get('/analytics/sales-report', [AnalyticsController::class, 'salesReport']);
            Route::get('/analytics/best-selling-products', [AnalyticsController::class, 'bestSellingProducts']);
            Route::get('/analytics/order-statistics', [AnalyticsController::class, 'orderStatistics']);
            Route::get('/analytics/revenue-statistics', [AnalyticsController::class, 'revenueStatistics']);
            Route::get('/analytics/customer-analytics', [AnalyticsController::class, 'customerAnalytics']);
            Route::get('/analytics/product-performance', [AnalyticsController::class, 'productPerformance']);
            Route::get('/analytics/payment-methods-breakdown', [AnalyticsController::class, 'paymentMethodsBreakdown']);
        });

        Route::middleware('role:user')->group(function () {
            Route::get('/cart', [CartItemController::class, 'index']);
            Route::post('/cart', [CartItemController::class, 'store']);
            Route::put('/cart/{id}', [CartItemController::class, 'update']);
            Route::delete('/cart/{id}', [CartItemController::class, 'destroy']);

            Route::post('/orders', [PaymentController::class, 'placeOrder']);

            Route::get('/orders', [OrderController::class, 'index']);
            Route::get('/orders/{id}', [OrderController::class, 'show']);
            Route::post('/orders/cancel', [OrderController::class, 'cancelOrder']);
        });

        Route::group(['middleware' => ['role_or_permission:user|edit profile']], function () {
            Route::post('/user/update/{id}', [UpdateProfileController::class, 'updateProfile']);
        });

        Route::group(['middleware' => ['permission:update order status']], function () {
            Route::post('/orders/status/{id}', [OrderStatusController::class, 'updateStatus']);
        });
    });
});
