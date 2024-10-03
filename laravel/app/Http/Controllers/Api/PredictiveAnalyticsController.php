<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
    public function getOverviewDemandForecast(): JsonResponse
    {
        $demandForecast = $this->demandForecastInterface->getOverviewDemandForecast();

        return Json::success($demandForecast);
    }

    public function getCategoryDemandForecast()
    {
    }

    public function getProductDemandForecast()
    {
    }
}

