<?php

use App\Http\Controllers\Api\Angular\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [OrderController::class, 'index'])->name('index');
