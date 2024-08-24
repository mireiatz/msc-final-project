<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Analytics\GetOverviewMetricsRequest;
use App\Http\Requests\Api\Analytics\GetProductsMetricsRequest;
use App\Http\Requests\Api\Analytics\GetSalesMetricsRequest;
use App\Services\Analytics\OverviewMetricsInterface;
use App\Services\Analytics\ProductsMetricsInterface;
use App\Services\Analytics\SalesMetricsInterface;
use App\Services\Analytics\StockMetricsInterface;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\JsonResponse as Json;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly OverviewMetricsInterface $overviewMetricsInterface,
        private readonly StockMetricsInterface    $stockMetricsInterface,
        private readonly SalesMetricsInterface    $salesMetricsInterface,
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
        $metrics = $this->overviewMetricsInterface->getMetrics($data['start_date'], $data['end_date']);

        return response()->json([
            'data' => $metrics,
            'success' => true,
        ]);
    }

    /**
     * Get stock metrics.
     *
     * @return JsonResponse
     */
    public function getStockMetrics(): JsonResponse
    {
        $metrics = $this->stockMetricsInterface->getDetailedMetrics();

        return Json::success($metrics);
    }

    /**
     * Get sales metrics.
     *
     * @param GetSalesMetricsRequest $request
     * @return JsonResponse
     */
    public function getSalesMetrics(GetSalesMetricsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $metrics = $this->salesMetricsInterface->getDetailedMetrics($data['start_date'], $data['end_date']);

        return Json::success($metrics);
    }

    /**
     * Get products metrics.
     *
     * @param GetProductsMetricsRequest $request
     * @return JsonResponse
     */
    public function getProductsMetrics(GetProductsMetricsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $metrics = $this->productsMetricsInterface->getDetailedMetrics($data['start_date'], $data['end_date']);

        return Json::paginate($metrics);
    }
}
