<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SupplierController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::get('health', fn() => response()->json([
        'status'  => 'ok',
        'version' => 'v1',
        'time'    => now()->toISOString(),
    ]));

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });

        Route::prefix('products')->group(function () {
            Route::get('trashed', [ProductController::class, 'trashed']);
            Route::post('{id}/restore', [ProductController::class, 'restore']);
            Route::delete('{id}/force', [ProductController::class, 'forceDelete']);
        });

        Route::apiResource('products', ProductController::class);
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('suppliers', SupplierController::class);
    });
});