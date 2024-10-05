<?php

namespace App\Services\PrescriptiveAnalytics;

use App\Models\Category;
use App\Models\Product;
use App\Models\Provider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReorderService implements ReorderInterface
{
    /**
     * Get reorder suggestions based on a provider and category.
     *
     * @param Provider $provider
     * @param Category $category
     * @return array
     */
    public function getReorderSuggestions(Provider $provider, Category $category): array
    {
        // Fetch the products with necessary relationships
        $leadDays = $provider->lead_days;
        $startDate = now()->addDays($leadDays); // Arrival date based on the provider's lead days
        $endDate = $startDate->clone()->addDays(7); // 7 day ordering cycle
        $products = $this->getProducts($provider->id, $category->id, $startDate, $endDate);

        // Organise the info for each product
        $reorderSuggestions = [];
        foreach ($products as $product) {
            // Fetch the subquery-calculated details
            $predictedDemand = (int)$product->total_predicted_demand;
            $maxDailyDemand = $product->max_quantity ?? 0;
            $avgDailyDemand = $product->avg_quantity ?? 0;
            $stockBalance = $product->stock_balance ?? 0;

            // Calculate a stock buffer and reorder amount
            $safetyStock = $this->calculateSafetyStock($maxDailyDemand, $avgDailyDemand, $leadDays);
            $reorderSuggestion = $this->calculateReorderSuggestion($predictedDemand, $safetyStock, $stockBalance);

            $reorderSuggestions[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'unit' => $product->unit,
                'amount_per_unit' => $product->amount_per_unit,
                'stock_balance' => $stockBalance,
                'predicted_demand' => $predictedDemand,
                'safety_stock' => $safetyStock,
                'reorder_amount' => $reorderSuggestion,
                'cost_per_unit' => round(($product->cost / 100), 2),
                'total_cost' => round((($product->cost / 100) * $reorderSuggestion), 2),
            ];
        }

        return $reorderSuggestions;
    }

    /**
     * Get products filtered by provider and category, fetching max/avg sales, total predicted demand and stock balance using subqueries for efficiency.
     *
     * @param string $providerId
     * @param string $categoryId
     * @param string $startDate
     * @param string $endDate
     * @return Collection
     */
    public function getProducts(string $providerId, string $categoryId, string $startDate, string $endDate): Collection
    {
        return Product::query()
            ->select('products.*',
                // Subquery for max quantity from sales
                DB::raw('(SELECT MAX(sp.quantity) FROM sale_products sp WHERE sp.product_id = products.id) as max_quantity'),
                // Subquery for average quantity from sales
                DB::raw('(SELECT AVG(sp.quantity) FROM sale_products sp WHERE sp.product_id = products.id) as avg_quantity'),
                // Subquery for total predicted demand between the date range
                DB::raw('(SELECT SUM(p.value) FROM predictions p WHERE p.product_id = products.id AND p.date BETWEEN ? AND ?) as total_predicted_demand'), // Dates bound below
                // Subquery to calculate the stock balance by summing inventory transactions
                DB::raw('(SELECT SUM(it.quantity) FROM inventory_transactions it WHERE it.product_id = products.id) as stock_balance')
            )
            ->setBindings([$startDate, $endDate], 'select')  // Bind the date parameters
            ->where('provider_id', $providerId)
            ->where('category_id', $categoryId)
            ->get();
    }

    /**
     * Calculate safety stock based on the difference between max and average daily demand.
     *
     * @param float $maxDailyDemand
     * @param float $avgDailyDemand
     * @param int $leadDays
     * @return int
     */
    public function calculateSafetyStock(float $maxDailyDemand, float $avgDailyDemand, int $leadDays): int
    {
        // Apply the formula
        $buffer = ($maxDailyDemand * $leadDays) - ($avgDailyDemand * $leadDays);

        // Return as int
        return max(0, round($buffer));
    }

    /**
     * Calculate reorder suggestion based on predicted demand, safety stock, and stock balance.
     *
     * @param float $predictedDemand
     * @param float $safetyStock
     * @param int $stockBalance
     * @return int
     */
    public function calculateReorderSuggestion(float $predictedDemand, float $safetyStock, int $stockBalance): int
    {
        // Calculate the required stock
        $requiredStock = $predictedDemand + $safetyStock;

        // Calculate the reorder suggestion
        $reorderSuggestion = $requiredStock - $stockBalance;

        // Return as int
        return max(0, round($reorderSuggestion));
    }
}
