<?php

use Illuminate\Support\Facades\Route;

Route::prefix('categories')->group(__DIR__ . '/api/v1/categories.php');
Route::prefix('providers')->group(__DIR__ . '/api/v1/providers.php');
Route::prefix('products')->group(__DIR__ . '/api/v1/products.php');
Route::prefix('sales')->group(__DIR__ . '/api/v1/sales.php');
Route::prefix('orders')->group(__DIR__ . '/api/v1/orders.php');
Route::prefix('stores')->group(__DIR__ . '/api/v1/stores.php');
Route::prefix('inventory-transactions')->group(__DIR__ . '/api/v1/inventory-transactions.php');
Route::prefix('analytics')->group(__DIR__ . '/api/v1/analytics.php');
