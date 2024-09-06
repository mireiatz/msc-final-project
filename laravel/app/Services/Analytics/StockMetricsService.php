<?php

namespace App\Services\Analytics;

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
        $products = Product::all();

        $productsInStockCount = $this->countProductsInStock($products);
        $productsOutOfStockCount = $this->countProductsOutOfStock($products);
        $lowStockProducts = $this->getLowStockProducts($products);
        $excessiveStockProducts = $this->getExcessiveStockProducts($products);
        $inventoryValue = $this->calculateInventoryValue($products);
        $totalItemsInStock = $this->calculateTotalItemsInStock($products);
        $productCount = $products->count();

        return [
            'inventory_value' => $inventoryValue,
            'total_items_in_stock' => $totalItemsInStock,
            'products_in_stock_count' => $productsInStockCount,
            'products_out_of_stock_count' => $productsOutOfStockCount,
            'low_stock_products' => $lowStockProducts,
            'excessive_stock_products' => $excessiveStockProducts,
            'product_count' => $productCount,
        ];
    }

    /**
     * Calculate the inventory value for all products.
     *
     * @param Collection $products
     * @return int
     */
    public function calculateInventoryValue(Collection $products): int
    {
        return $products->reduce(function ($carry, $product) {
            $productValue = $product->stock_balance * $product->cost;
            return $carry + $productValue;
        }, 0) / 100;
    }

    /**
     * Calculate the total number of items in stock.
     *
     * @param Collection $products
     * @return int
     */
    public function calculateTotalItemsInStock(Collection $products): int
    {
        return $products->sum('stock_balance');
    }

    /**
     * Count the number of products currently in stock.
     *
     * @param Collection $products
     * @return int
     */
    public function countProductsInStock(Collection $products): int
    {
        return $products->filter(function ($product) {
            return $product->stock_balance > 0;
        })->count();
    }

    /**
     * Count the number of products currently out of stock.
     *
     * @param Collection $products
     * @return int
     */
    public function countProductsOutOfStock(Collection $products): int
    {
        return $products->filter(function ($product) {
            return $product->stock_balance <= 0;
        })->count();
    }

    /**
     * Get products with stock levels below their minimum.
     *
     * @param Collection $products
     * @return array
     */
    public function getLowStockProducts(Collection $products): array
    {
        return $products->filter(function ($product) {
            return $product->stock_balance < $product->min_stock_level;
        })->values()->mapWithKeys(function ($product, $index) {
            return [$index => [
                'id' => $product->id,
                'name' => $product->name,
            ]];
        })->toArray();
    }

    /**
     * Get products with stock levels above their maximum.
     *
     * @param Collection $products
     * @return array
     */
    public function getExcessiveStockProducts(Collection $products): array
    {
        return $products->filter(function ($product) {
            return $product->stock_balance > $product->max_stock_level;
        })->values()->mapWithKeys(function ($product, $index) {
            return [$index => [
                'id' => $product->id,
                'name' => $product->name,
            ]];
        })->toArray();
    }

    /**
     * Get detailed stock metrics grouped by product category.
     *
     * @return array
     */
    public function getDetailedMetrics(): array
    {
        $products = $this->getProductsGroupedByCategory();

        return $this->mapProducts($products);
    }

    /**
     * Get products grouped by category.
     *
     * @return Collection
     */
    public function getProductsGroupedByCategory(): Collection
    {
        return Product::with('category')->get()->groupBy('category_id');
    }

    /**
     * Map product details for stock reporting purposes.
     *
     * @param Collection $products
     * @return array
     */
    public function mapProducts(Collection $products): array
    {
        return $products->map(function ($products) {
            return [
                'category' => [
                    'id' => $products->first()->category_id,
                    'name' => $products->first()->category->name,
                ],
                'products' => $products->map(fn ($product) => $this->mapProductDetails($product)),
            ];
        })->values()->toArray();
    }

    /**
     * Map detailed product information.
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
