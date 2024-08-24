<?php

use App\Http\Controllers\Api\Angular\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('/overview')->group(function () {
    Route::post('/', [AnalyticsController::class, 'getOverviewMetrics'])->name('getOverviewMetrics');
});

Route::prefix('/stock')->group(function () {
    Route::get('/', [AnalyticsController::class, 'getStockMetrics'])->name('getStockMetrics');
});

Route::prefix('/products')->group(function () {
    Route::post('/', [AnalyticsController::class, 'getProductsMetrics'])->name('getProductsMetrics');
});

Route::prefix('/sales')->group(function () {
    Route::post('/', [AnalyticsController::class, 'getSalesMetrics'])->name('getSalesMetrics');
});

