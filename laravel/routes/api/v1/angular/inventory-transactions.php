<?php

use App\Http\Controllers\Api\Angular\InventoryTransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [InventoryTransactionController::class, 'index'])->name('index');
