<?php

namespace App\Services\DescriptiveAnalytics;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;

class StockMetricsService implements StockMetricsInterface
{
    /**
     * Get an overview of stock metrics.
     *
     * @return array
     */
    public function getOverviewMetrics(): array
    {
        $products = Product::with('category')->get();

        // Compute metrics for the products
        $metrics = $this->calculateStockMetrics($products);
        return [
            'inventory_value' => $metrics['inventory_value'],
            'total_items_in_stock' => $metrics['total_items_in_stock'],
            'products_in_stock_count' => $metrics['products_in_stock_count'],
            'products_out_of_stock_count' => $metrics['products_out_of_stock_count'],
            'low_stock_products' => $metrics['low_stock_products'],
            'excessive_stock_products' => $metrics['excessive_stock_products'],
            'product_count' => $products->count(),
        ];
    }

    /**
     * Calculate stock-related metrics for all products in one pass.
     *
     * @param Collection $products
     * @return array
     */
    public function calculateStockMetrics(Collection $products): array
    {
        $inventoryValue = 0;
        $totalItemsInStock = 0;
        $productsInStockCount = 0;
        $productsOutOfStockCount = 0;
        $lowStockProducts = [];
        $excessiveStockProducts = [];

        foreach ($products as $product) {
            $stockBalance = $product->stock_balance;

            $inventoryValue += $stockBalance * $product->cost;
            $totalItemsInStock += $stockBalance;

            if ($stockBalance > 0) {
                $productsInStockCount++;
            } else {
                $productsOutOfStockCount++;
            }

            if ($stockBalance < $product->min_stock_level) {
                $lowStockProducts[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                ];
            }

            if ($stockBalance > $product->max_stock_level) {
                $excessiveStockProducts[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                ];
            }
        }

        return [
            'inventory_value' => $inventoryValue / 100, // Cost is in cents
            'total_items_in_stock' => $totalItemsInStock,
            'products_in_stock_count' => $productsInStockCount,
            'products_out_of_stock_count' => $productsOutOfStockCount,
            'low_stock_products' => $lowStockProducts,
            'excessive_stock_products' => $excessiveStockProducts,
        ];
    }

    /**
     * Get detailed stock metrics for a specific category.
     *
     * @param Category $category
     * @return array
     */
    public function getDetailedMetrics(Category $category): array
    {
        // Fetch products for the given category
        $products = Product::where('category_id', $category->id)->get();

        // Map and return detailed product metrics for the specific category
        return [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
            ],
            'products' => $this->mapProducts($products),
        ];
    }

    /**
     * Map products.
     *
     * @param Collection $products
     * @return array
     */
    public function mapProducts(Collection $products): array
    {
        return $products->map(fn($product) => $this->mapProductDetails($product))->toArray();
    }

    /**
     * Map product information.
     *
     * @param Product $product
     * @return array
     */
    private function mapProductDetails(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'min' => $product->min_stock_level,
            'max' => $product->max_stock_level,
            'current' => $product->stock_balance,
            'range' => $product->max_stock_level - $product->min_stock_level,
            'status' => $this->getStockStatus($product->stock_balance, $product->min_stock_level, $product->max_stock_level),
        ];
    }

    /**
     * Get the stock status (understocked, overstocked, or within range) for a product.
     *
     * @param int $balance
     * @param int $min
     * @param int $max
     * @return string
     */
    public function getStockStatus(int $balance, int $min, int $max): string
    {
        if ($balance < $min) {
            return 'understocked';
        } elseif ($balance > $max) {
            return 'overstocked';
        }
        return 'within_range';
    }
}
