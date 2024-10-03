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
                    'category' => $prediction->category_name,
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
                    'product_id' => $prediction->product_id,
                    'product_name' => $prediction->product_name,
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

}
