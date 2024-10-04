<?php

use App\Http\Controllers\Api\DescriptiveAnalyticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('/overview')->group(function () {
    Route::post('/', [DescriptiveAnalyticsController::class, 'getOverviewMetrics'])->name('getOverviewMetrics');
});

Route::prefix('/sales')->group(function () {
    Route::post('/', [DescriptiveAnalyticsController::class, 'getSalesMetrics'])->name('getSalesMetrics');
});

Route::prefix('/products')->group(function () {

    Route::prefix('/{product}')->group(function () {
        Route::post('/', [DescriptiveAnalyticsController::class, 'getProductMetrics'])->name('getProductMetrics');
    });
});

Route::prefix('/categories/{category}')->group(function () {

    Route::get('/stock', [DescriptiveAnalyticsController::class, 'getCategoryStockMetrics'])->name('getCategoryStockMetrics');

    Route::post('/products', [DescriptiveAnalyticsController::class, 'getCategoryProductsMetrics'])->name('getCategoryProductsMetrics');
});
