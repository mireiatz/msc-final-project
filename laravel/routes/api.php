<?php

use Illuminate\Support\Facades\Route;

Route::prefix('angular')->group(__DIR__ . '/api/v1/angular.php');
Route::prefix('ml')->group(__DIR__ . '/api/v1/ml.php');
