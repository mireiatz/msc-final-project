<?php

use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProductController::class, 'index'])->name('index');
