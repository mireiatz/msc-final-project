<?php

use App\Http\Controllers\Api\PrescriptiveAnalyticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('/reorder')->group(function () {

    Route::get('/{provider}/{category}', [PrescriptiveAnalyticsController::class, 'getReorderSuggestions'])->name('getReorderSuggestions');
});
