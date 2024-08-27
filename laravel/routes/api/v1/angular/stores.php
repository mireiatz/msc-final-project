<?php

use App\Http\Controllers\Api\Angular\StoreController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StoreController::class, 'index'])->name('index');
