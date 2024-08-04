<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GetOverviewMetricsRequest;
use App\Services\Analytics\OverviewMetricsInterface;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function __construct(private readonly OverviewMetricsInterface $overviewMetricsInterface)
    {}

    /**
     * Get overview metrics.
     *
     * @param GetOverviewMetricsRequest $request
     * @return JsonResponse
     */
    public function getOverviewMetrics(GetOverviewMetricsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $metrics = $this->overviewMetricsInterface->getOverviewMetrics($data['period']);

        return response()->json([
            'data' => $metrics,
            'success' => true,
        ]);
    }
}
