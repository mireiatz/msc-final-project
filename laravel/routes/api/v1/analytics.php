<?php

use App\Http\Controllers\Api\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('/overview')->group(function () {
    Route::post('/', [AnalyticsController::class, 'getOverviewMetrics'])->name('getOverviewMetrics');
});

