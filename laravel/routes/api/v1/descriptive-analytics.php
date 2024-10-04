<?php

use App\Http\Controllers\Api\DescriptiveAnalyticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('/overview')->group(function () {
    Route::post('/', [DescriptiveAnalyticsController::class, 'getOverviewMetrics'])->name('getOverviewMetrics');
});

Route::prefix('/stock')->group(function () {
    Route::get('/', [DescriptiveAnalyticsController::class, 'getStockMetrics'])->name('getStockMetrics');
});

Route::prefix('/products')->group(function () {
    Route::post('/', [DescriptiveAnalyticsController::class, 'getProductsMetrics'])->name('getProductsMetrics');

    Route::prefix('/{product}')->group(function () {
        Route::post('/', [DescriptiveAnalyticsController::class, 'getProductMetrics'])->name('getProductMetrics');
    });
});

Route::prefix('/sales')->group(function () {
    Route::post('/', [DescriptiveAnalyticsController::class, 'getSalesMetrics'])->name('getSalesMetrics');
});

