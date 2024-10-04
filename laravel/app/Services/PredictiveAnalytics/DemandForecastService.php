<?php

namespace App\Services\PredictiveAnalytics;


use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DemandForecastService implements DemandForecastInterface
{

    public function getOverviewDemandForecast(): array
    {
        $today = Carbon::today();

        // Fetch predictions
        $predictions = DB::table('predictions')
            ->join('products', 'predictions.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('categories.id as category_id', 'categories.name as category_name', 'predictions.date', DB::raw('SUM(predictions.value) as total_value')) // Aggregate values
            ->where('predictions.date', '>=', $today)
            ->groupBy('categories.id', 'predictions.date')
            ->orderBy('predictions.date')
            ->get();

        // Format the results
        $aggregatedResults = [];
        foreach ($predictions as $prediction) {
            // Check if the category exists in the results array
            if (!isset($aggregatedResults[$prediction->category_id])) {
                $aggregatedResults[$prediction->category_id] = [
                    'id' => $prediction->category_id,
                    'name' => $prediction->category_name,
                    'predictions' => [] // Initialise predictions array
                ];
            }

            // Push the aggregated prediction as an array of objects with date and value
            $aggregatedResults[$prediction->category_id]['predictions'][] = [
                'date' => $prediction->date,
                'value' => $prediction->total_value
            ];
        }

        // Sort the dates in the predictions
        foreach ($aggregatedResults as &$category) {
            usort($category['predictions'], function($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
            });
        }

        // Return as an indexed array
        return array_values($aggregatedResults);
    }

    public function getCategoryDemandForecast($category): array
    {
        $today = Carbon::today();

        // Fetch predictions for a specific category
        $predictions = DB::table('predictions')
            ->join('products', 'predictions.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'categories.name as category_name',
                'products.id as product_id',
                'products.name as product_name',
                'predictions.date',
                DB::raw('SUM(predictions.value) as total_value') // Aggregate prediction values
            )
            ->where('categories.id', $category->id)
            ->where('predictions.date', '>=', $today) // Only include future or today's predictions
            ->groupBy('products.id', 'predictions.date')
            ->orderBy('predictions.date')
            ->get();

        // Format the results
        $formattedResults = [
            'category' => '',
            'products' => []
        ];

        foreach ($predictions as $prediction) {
            // If category name is empty, assign the current category name
            if (empty($formattedResults['category'])) {
                $formattedResults['category'] = $prediction->category_name;
            }

            // Initialise the product entry if it doesn't exist
            if (!isset($formattedResults['products'][$prediction->product_id])) {
                $formattedResults['products'][$prediction->product_id] = [
                    'id' => $prediction->product_id,
                    'name' => $prediction->product_name,
                    'predictions' => [] // Initialise the predictions array
                ];
            }

            // Add the prediction with date and value to the product's predictions array
            $formattedResults['products'][$prediction->product_id]['predictions'][] = [
                'date' => $prediction->date,
                'value' => $prediction->total_value
            ];
        }

        // Return as a sequential array
        $formattedResults['products'] = array_values($formattedResults['products']);

        return $formattedResults;
    }

    public function getMonthAggregatedDemandForecast(): array
    {
        $next30DaysForecast = DB::table('predictions')
            ->join('products', 'predictions.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'categories.name as category_name',
                'categories.id as category_id',
                DB::raw('SUM(predictions.value) as total_demand')
            )
            ->where('predictions.date', '>=', now())
            ->where('predictions.date', '<=', now()->addDays(30))
            ->groupBy('categories.id', 'categories.name')
            ->get();


        return $next30DaysForecast->map(function($category) {
            return [
                'id' => $category->category_id,
                'name' => $category->category_name,
                'value' => $category->total_demand
            ];
        })->toArray();
    }

    public function getWeeklyAggregatedDemandForecast(): array
    {
        $nextMonday = now()->next('Monday');

        // Get weekly forecast
        $weeklyForecast = DB::table('predictions')
            ->join('products', 'predictions.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'categories.name as category_name',
                'categories.id as category_id',
                DB::raw('FLOOR(DATEDIFF(predictions.date, "' . $nextMonday . '") / 7) as week_number'),
                DB::raw('SUM(predictions.value) as total_demand')
            )
            ->where('predictions.date', '>=', $nextMonday)
            ->where('predictions.date', '<=', $nextMonday->copy()->addDays(34)) // To cover 4 weeks
            ->groupBy('categories.id', 'categories.name', 'week_number')
            ->orderBy('categories.name')
            ->orderBy('week_number')
            ->get();

        // Structure the data
        $groupedResults = [];
        foreach ($weeklyForecast as $forecast) {
            if (!isset($groupedResults[$forecast->category_name])) {
                $groupedResults[$forecast->category_name] = [
                    'id' => $forecast->category_id,
                    'name' => $forecast->category_name,
                    'weeks' => []
                ];
            }

            $groupedResults[$forecast->category_name]['weeks'][] = [
                'name' => 'Week ' . ($forecast->week_number + 1), // Week 1, Week 2, etc.
                'value' => $forecast->total_demand
            ];
        }

        // Return standard array
        return array_values($groupedResults);
    }
}
