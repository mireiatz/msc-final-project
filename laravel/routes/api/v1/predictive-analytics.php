<?php

use App\Http\Controllers\Api\PredictiveAnalyticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('/demand-forecast')->group(function () {

    Route::get('/category-level', [PredictiveAnalyticsController::class, 'getCategoryLevelDemandForecast'])->name('getCategoryLevelDemandForecast');

    Route::prefix('/product-level')->group(function () {

        Route::prefix('/categories')->group(function () {

            Route::prefix('/{category}')->group(function () {

                Route::get('/', [PredictiveAnalyticsController::class, 'getProductLevelDemandForecast'])->name('getProductLevelDemandForecast');
            });
        });
    });

    Route::get('/month', [PredictiveAnalyticsController::class, 'getMonthAggregatedDemandForecast'])->name('getMonthAggregatedDemandForecast');

    Route::get('/weekly', [PredictiveAnalyticsController::class, 'getWeeklyAggregatedDemandForecast'])->name('getWeeklyAggregatedDemandForecast');
});
