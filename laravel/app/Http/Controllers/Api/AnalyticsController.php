<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GetOverviewMetricsRequest;
use App\Services\Analytics\ProductsMetricsInterface;
use App\Services\Analytics\SalesMetricsInterface;
use App\Services\Analytics\StockMetricsInterface;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly StockMetricsInterface $stockMetricsInterface,
        private readonly SalesMetricsInterface $salesMetricsInterface,
        private readonly ProductsMetricsInterface $productsMetricsInterface,
    )
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
        $metrics['stock'] = $this->stockMetricsInterface->getMetrics();
        $metrics['sales'] = $this->salesMetricsInterface->getMetrics($data['period']);
        $metrics['products'] = $this->productsMetricsInterface->getMetrics($data['period']);

        return response()->json([
            'data' => $metrics,
            'success' => true,
        ]);
    }
}
