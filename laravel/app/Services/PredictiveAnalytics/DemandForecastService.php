<?php

namespace App\Services\PredictiveAnalytics;


use App\Models\Category;
use App\Models\Prediction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DemandForecastService implements DemandForecastInterface
{

    /**
     * Get an overview of aggregated demand forecasts for all categories.
     *
     * @return array
     */
    public function getCategoryLevelDemandForecast(): array
    {
        $today = Carbon::today();

        // Fetch predictions aggregated by category
        $predictions = Prediction::query()
            ->join('products', 'predictions.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('categories.id as category_id', 'categories.name as category_name', 'predictions.date', DB::raw('SUM(predictions.value) as total_value')) // Aggregate prediction values
            ->where('predictions.date', '>=', $today)
            ->groupBy('categories.id', 'predictions.date')
            ->orderBy('predictions.date')
            ->get();

        // Aggregate and format the results by category
        return $this->formatPredictionsByGroup($predictions, 'category_id', 'category_name');
    }

    /**
     * Get product-level demand forecasts for a specified category.
     *
     * @param Category $category
     * @return array
     */
    public function getProductLevelDemandForecast(Category $category): array
    {
        $today = Carbon::today();

        // Fetch predictions for products in the specific category
        $predictions = DB::table('predictions')
            ->join('products', 'predictions.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'categories.name as category_name',
                'products.id as product_id',
                'products.name as product_name',
                'predictions.date',
                'predictions.value as total_value'  // Only one prediction per product, per day (no aggregation)
            )
            ->where('categories.id', $category->id)
            ->where('predictions.date', '>=', $today) // Only include future or today's predictions
            ->orderBy('predictions.date')
            ->get();

        // Format the results by product
        return [
            'category' => $category->name,
            'products' => $this->formatPredictionsByGroup($predictions, 'product_id', 'product_name')
        ];
    }

    /**
     * Get a weekly aggregated 4-week demand forecast for a specific category.
     *
     * @param Category $category
     * @return array
     */
    public function getWeeklyAggregatedDemandForecast(Category $category): array
    {
        // Get start/end dates for weekly forecasts
        $nextMonday = Carbon::now()->next('Monday');
        $fourWeeksLater = $nextMonday->copy()->addWeeks(4);

        // Fetch raw predictions for the next 4 weeks
        $predictions = DB::table('predictions')
            ->join('products', 'predictions.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'categories.id as category_id',
                'predictions.date',
                'predictions.value'
            )
            ->where('categories.id', $category->id)
            ->whereBetween('predictions.date', [$nextMonday, $fourWeeksLater])
            ->orderBy('predictions.date')
            ->get();

        // Group predictions by week
        $weeklyForecast = [];

        foreach ($predictions as $prediction) {
            $weekStart = Carbon::parse($prediction->date)->startOfWeek();
            $weekLabel = $weekStart->format('Y-m-d');

            if (!isset($weeklyForecast[$weekLabel])) {
                $weeklyForecast[$weekLabel] = 0;
            }

            $weeklyForecast[$weekLabel] += $prediction->value;
        }

        // Format into forecast
        $formattedResults = [
            'id' => $category->id,
            'name' => $category->name,
            'weeks' => []
        ];

        foreach ($weeklyForecast as $weekStart => $totalDemand) {
            $formattedResults['weeks'][] = [
                'name' => 'Week of ' . Carbon::parse($weekStart)->format('d-m-Y'),
                'value' => $totalDemand
            ];
        }

        return $formattedResults;
    }

    /**
     * Get 30-day-aggregated demand predictions, by category.
     *
     * @return array
     */
    public function getMonthAggregatedDemandForecast(): array
    {
        // Calculate the forecast for the next 30 days
        $next30DaysForecast = DB::table('predictions')
            ->join('products', 'predictions.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'categories.name as category_name',
                'categories.id as category_id',
                DB::raw('SUM(predictions.value) as total_demand') // Aggregate prediction values
            )
            ->where('predictions.date', '>=', Carbon::today())
            ->where('predictions.date', '<=', Carbon::today()->addDays(30))
            ->groupBy('categories.id')
            ->orderBy('total_demand', 'desc')
            ->get();

        // Format the result as array
        return $next30DaysForecast->map(function ($category) {
            return [
                'id' => $category->category_id,
                'name' => $category->category_name,
                'value' => $category->total_demand
            ];
        })->toArray();
    }

    /**
     * Format predictions grouped by a key (category or product).
     *
     * @param Collection $predictions
     * @param string $groupByField The field to group by (e.g., 'category' or 'product')
     * @param string $groupNameField The name of the field to display (e.g., 'category_name' or 'product_name')
     * @return array
     */
    protected function formatPredictionsByGroup(Collection $predictions, string $groupByField, string $groupNameField): array
    {
        $formattedResults = [];

        foreach ($predictions as $prediction) {
            // Initialise the group if it doesn't exist
            if (!isset($formattedResults[$prediction->$groupByField])) {
                $formattedResults[$prediction->$groupByField] = [
                    'id' => $prediction->$groupByField,
                    'name' => $prediction->$groupNameField,
                    'predictions' => []  // Initialise predictions array
                ];
            }

            // Format the date and add the prediction
            $formattedDate = Carbon::parse($prediction->date)->format('d-m-Y');
            $formattedResults[$prediction->$groupByField]['predictions'][] = [
                'date' => $formattedDate,
                'value' => $prediction->total_value
            ];
        }

        // Return as a sequential array
        return array_values($formattedResults);
    }
}
