<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Provider;
use App\Services\PrescriptiveAnalytics\ReorderInterface;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\JsonResponse as Json;

class PrescriptiveAnalyticsController extends Controller
{

    public function __construct(
        private readonly ReorderInterface $reorderInterface,
    ) {}

    /**
     * Get overview metrics for the specified date range.
     *
     * @param Provider $provider
     * @param Category $category
     * @return JsonResponse
     */
    public function getReorderSuggestions(Provider $provider, Category $category): JsonResponse
    {
        $reorders = $this->reorderInterface->getReorderSuggestions($provider, $category);

        return Json::paginate($reorders);
    }
}
