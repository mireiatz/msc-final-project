<?php

use App\Http\Controllers\Api\PredictiveAnalyticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('/demand-forecast')->group(function () {

    Route::get('/category-level', [PredictiveAnalyticsController::class, 'getCategoryLevelDemandForecast'])->name('getCategoryLevelDemandForecast');

    Route::prefix('/categories')->group(function () {

        Route::prefix('/{category}')->group(function () {

            Route::get('/product-level', [PredictiveAnalyticsController::class, 'getProductLevelDemandForecast'])->name('getProductLevelDemandForecast');

            Route::get('/weekly', [PredictiveAnalyticsController::class, 'getWeeklyAggregatedDemandForecast'])->name('getWeeklyAggregatedDemandForecast');
        });
    });

    Route::get('/month', [PredictiveAnalyticsController::class, 'getMonthAggregatedDemandForecast'])->name('getMonthAggregatedDemandForecast');

});
