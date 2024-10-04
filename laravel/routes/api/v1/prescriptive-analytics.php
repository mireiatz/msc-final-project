<?php

use App\Http\Controllers\Api\PrescriptiveAnalyticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('/reorder')->group(function () {

    Route::prefix('/providers/{provider}')->group(function () {

        Route::prefix('/categories/{category}')->group(function () {

            Route::get('/', [PrescriptiveAnalyticsController::class, 'getReorderSuggestions'])->name('getReorderSuggestions');
        });
    });
});
