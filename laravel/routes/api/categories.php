<?php

use App\Http\Controllers\Api\CategoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CategoryController::class, 'index'])->name('index');
