<?php

use Illuminate\Support\Facades\Route;

Route::prefix('categories')->group(__DIR__ . '/api/categories.php');
Route::prefix('providers')->group(__DIR__ . '/api/providers.php');
Route::prefix('products')->group(__DIR__ . '/api/products.php');
Route::prefix('sales')->group(__DIR__ . '/api/sales.php');
Route::prefix('orders')->group(__DIR__ . '/api/orders.php');
Route::prefix('stores')->group(__DIR__ . '/api/stores.php');
Route::prefix('inventory-transactions')->group(__DIR__ . '/api/inventory-transactions.php');
