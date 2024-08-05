<?php

namespace App\Providers;

use App\Services\Analytics\ProductsMetricsInterface;
use App\Services\Analytics\ProductsMetricsService;
use App\Services\Analytics\SalesMetricsInterface;
use App\Services\Analytics\SalesMetricsService;
use App\Services\Analytics\StockMetricsInterface;
use App\Services\Analytics\StockMetricsService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(StockMetricsInterface::class, StockMetricsService::class);
        $this->app->bind(SalesMetricsInterface::class, SalesMetricsService::class);
        $this->app->bind(ProductsMetricsInterface::class, ProductsMetricsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
