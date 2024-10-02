<?php

namespace App\Providers;

use App\Services\Analytics\ProductsMetricsInterface;
use App\Services\Analytics\ProductsMetricsService;
use App\Services\Analytics\SalesMetricsInterface;
use App\Services\Analytics\SalesMetricsService;
use App\Services\Analytics\OverviewMetricsInterface;
use App\Services\Analytics\OverviewMetricsService;
use App\Services\Analytics\StockMetricsInterface;
use App\Services\Analytics\StockMetricsService;
use App\Services\ML\MLServiceClient;
use App\Services\ML\MLServiceClientInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OverviewMetricsInterface::class, OverviewMetricsService::class);
        $this->app->bind(StockMetricsInterface::class, StockMetricsService::class);
        $this->app->bind(SalesMetricsInterface::class, SalesMetricsService::class);
        $this->app->bind(ProductsMetricsInterface::class, ProductsMetricsService::class);
        $this->app->bind(MLServiceClientInterface::class, MLServiceClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
