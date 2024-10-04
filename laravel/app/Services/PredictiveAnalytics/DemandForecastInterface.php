<?php

namespace App\Services\PredictiveAnalytics;

use App\Models\Category;

interface DemandForecastInterface
{

    /**
     * Get the demand forecast aggregated by category.
     *
     * @return array
     */
    public function getCategoryLevelDemandForecast(): array;

    /**
     * Get the demand forecast for a category, with details per product.
     *
     * @param Category $category
     * @return array
     */
    public function getProductLevelDemandForecast(Category $category): array;

    /**
     * Get a weekly aggregated demand forecast for a category.
     *
     * @param Category $category
     * @return array
     */
    public function getWeeklyAggregatedDemandForecast(Category $category): array;


    /**
     * Get a demand forecast aggregated for a month, grouped by category.
     *
     * @return array
     */
    public function getMonthAggregatedDemandForecast(): array;
}
