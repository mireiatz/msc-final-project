<?php

namespace App\Providers;

use App\Services\Analytics\OverviewMetricsInterface;
use App\Services\Analytics\OverviewMetricsService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OverviewMetricsInterface::class, OverviewMetricsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
