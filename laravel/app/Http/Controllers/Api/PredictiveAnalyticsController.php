<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\PredictiveAnalytics\DemandForecastInterface;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\JsonResponse as Json;

class PredictiveAnalyticsController extends Controller
{

    public function __construct(
        private readonly DemandForecastInterface $demandForecastInterface,
    ) {}

    /**
     * Get overview metrics for the specified date range.
     *
     * @return JsonResponse
     */
    public function getCategoryLevelDemandForecast(): JsonResponse
    {
        $demandForecast = $this->demandForecastInterface->getCategoryLevelDemandForecast();

        return Json::success($demandForecast);
    }

    public function getProductLevelDemandForecast(Category $category): JsonResponse
    {
        $demandForecast = $this->demandForecastInterface->getProductLevelDemandForecast($category);

        return Json::success($demandForecast);
    }

    public function getWeeklyAggregatedDemandForecast(Category $category): JsonResponse
    {
        $demandForecast = $this->demandForecastInterface->getWeeklyAggregatedDemandForecast($category);

        return Json::success($demandForecast);
    }

    public function getMonthAggregatedDemandForecast(): JsonResponse
    {
        $demandForecast = $this->demandForecastInterface->getMonthAggregatedDemandForecast();

        return Json::success($demandForecast);
    }
}

