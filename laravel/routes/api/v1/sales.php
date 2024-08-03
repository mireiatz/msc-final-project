<?php

use App\Http\Controllers\Api\SaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SaleController::class, 'index'])->name('index');
