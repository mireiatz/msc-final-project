<?php

use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [OrderController::class, 'index'])->name('index');
