<?php

use App\Http\Controllers\Api\PredictiveAnalyticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('/demand-forecast')->group(function () {

    Route::get('/', [PredictiveAnalyticsController::class, 'getOverviewDemandForecast'])->name('getOverviewDemandForecast');

    Route::get('/month', [PredictiveAnalyticsController::class, 'getMonthAggregatedDemandForecast'])->name('getMonthAggregatedDemandForecast');

    Route::get('/weekly', [PredictiveAnalyticsController::class, 'getWeeklyAggregatedDemandForecast'])->name('getWeeklyAggregatedDemandForecast');

    Route::prefix('/categories')->group(function () {

        Route::prefix('/{category}')->group(function () {

            Route::get('/', [PredictiveAnalyticsController::class, 'getCategoryDemandForecast'])->name('getCategoryDemandForecast');
        });
    });
});
