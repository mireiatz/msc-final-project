<?php

use App\Http\Controllers\Api\ML\TrainingController;
use Illuminate\Support\Facades\Route;

Route::post('/train-models', [TrainingController::class, 'trainModels'])->name('trainModels');
