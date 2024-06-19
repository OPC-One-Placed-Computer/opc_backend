<?php

use App\Http\Controllers\Api\V1\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ProductController;

Route::prefix('V1')->group(function () {
    Route::apiResource('/tasks', TaskController::class);
    Route::apiResource('/products', ProductController::class);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

