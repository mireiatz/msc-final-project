<?php

use App\Http\Controllers\Api\InventoryTransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [InventoryTransactionController::class, 'index'])->name('inventory-transactions.index');
