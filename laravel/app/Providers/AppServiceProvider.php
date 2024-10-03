<?php

namespace App\Providers;

use App\Services\DescriptiveAnalytics\ProductsMetricsInterface;
use App\Services\DescriptiveAnalytics\ProductsMetricsService;
use App\Services\DescriptiveAnalytics\SalesMetricsInterface;
use App\Services\DescriptiveAnalytics\SalesMetricsService;
use App\Services\DescriptiveAnalytics\OverviewMetricsInterface;
use App\Services\DescriptiveAnalytics\OverviewMetricsService;
use App\Services\DescriptiveAnalytics\StockMetricsInterface;
use App\Services\DescriptiveAnalytics\StockMetricsService;
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
