<?php

use App\Http\Controllers\Api\PredictiveAnalyticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('/demand-forecast')->group(function () {

    Route::get('/', [PredictiveAnalyticsController::class, 'getOverviewDemandForecast'])->name('getOverviewDemandForecast');

    Route::prefix('/categories')->group(function () {

        Route::prefix('/{category}')->group(function () {

            Route::get('/', [PredictiveAnalyticsController::class, 'getCategoryDemandForecast'])->name('getCategoryDemandForecast');
        });
    });

    Route::prefix('/products')->group(function () {

        Route::prefix('/{product}')->group(function () {

            Route::post('/', [PredictiveAnalyticsController::class, 'getProductDemandForecast'])->name('getProductDemandForecast');
        });
    });
});
