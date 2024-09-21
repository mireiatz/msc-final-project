<?php

use App\Http\Controllers\Api\Angular\SaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SaleController::class, 'index'])->name('index');
