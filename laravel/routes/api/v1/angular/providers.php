<?php

use App\Http\Controllers\Api\Angular\ProviderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProviderController::class, 'index'])->name('index');
