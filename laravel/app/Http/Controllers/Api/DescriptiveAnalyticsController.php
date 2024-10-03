<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DateRangeRequest;
use App\Http\Responses\JsonResponse as Json;
use App\Models\Product;
use App\Services\DescriptiveAnalytics\OverviewMetricsInterface;
use App\Services\DescriptiveAnalytics\ProductsMetricsInterface;
use App\Services\DescriptiveAnalytics\SalesMetricsInterface;
use App\Services\DescriptiveAnalytics\StockMetricsInterface;
use Illuminate\Http\JsonResponse;

class DescriptiveAnalyticsController extends Controller
{
    public function __construct(
        private readonly OverviewMetricsInterface $overviewMetricsInterface,
        private readonly StockMetricsInterface    $stockMetricsInterface,
        private readonly SalesMetricsInterface    $salesMetricsInterface,
        private readonly ProductsMetricsInterface $productsMetricsInterface,
    ) {}

    /**
     * Get overview metrics for the specified date range.
     *
     * @param DateRangeRequest $request
     * @return JsonResponse
     */
    public function getOverviewMetrics(DateRangeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $metrics = $this->overviewMetricsInterface->getMetrics($data['start_date'], $data['end_date']);

        return Json::success($metrics);
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
     * Get sales metrics for the specified date range.
     *
     * @param DateRangeRequest $request
     * @return JsonResponse
     */
    public function getSalesMetrics(DateRangeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $metrics = $this->salesMetricsInterface->getDetailedMetrics($data['start_date'], $data['end_date']);

        return Json::success($metrics);
    }

    /**
     * Get products metrics for the specified date range.
     *
     * @param DateRangeRequest $request
     * @return JsonResponse
     */
    public function getProductsMetrics(DateRangeRequest $request): JsonResponse
    {
        $data = $request->validated();

        $metrics = $this->productsMetricsInterface->getDetailedMetrics($data['start_date'], $data['end_date']);

        return Json::paginate($metrics);
    }

    /**
     * Get metrics for a specific product for the specified date range.
     *
     * @param DateRangeRequest $request
     * @param Product $product
     * @return JsonResponse
     */
    public function getProductMetrics(DateRangeRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();

        $metrics = $this->productsMetricsInterface->getProductSpecificMetrics($product, $data['start_date'], $data['end_date']);

        return Json::success($metrics);
    }
}
