<?php

use Illuminate\Support\Facades\Route;

Route::prefix('categories')->group(__DIR__ . '/angular/categories.php');
Route::prefix('providers')->group(__DIR__ . '/angular/providers.php');
Route::prefix('products')->group(__DIR__ . '/angular/products.php');
Route::prefix('sales')->group(__DIR__ . '/angular/sales.php');
Route::prefix('orders')->group(__DIR__ . '/angular/orders.php');
Route::prefix('stores')->group(__DIR__ . '/angular/stores.php');
Route::prefix('inventory-transactions')->group(__DIR__ . '/angular/inventory-transactions.php');
Route::prefix('analytics')->group(__DIR__ . '/angular/analytics.php');
