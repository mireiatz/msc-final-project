<?php

namespace App\Services\Analytics;

use App\Models\Product;

class StockMetricsService implements StockMetricsInterface
{
    /**
     * Get overview analytics for the stock.
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

    public function calculateInventoryValue($products): int
    {
        return $products->reduce(function ($carry, $product) {
            $productValue = $product->stock_balance * $product->cost;
            return $carry + $productValue;
        }, 0) / 100;
    }

    public function calculateTotalItemsInStock($products): int
    {
        return $products->sum('stock_balance');
    }

    public function countProductsInStock($products): int
    {
        return $products->filter(function ($product) {
            return $product->stock_balance > 0;
        })->count();
    }

    public function countProductsOutOfStock($products): int
    {
        return $products->filter(function ($product) {
            return $product->stock_balance <= 0;
        })->count();
    }

    public function getLowStockProducts($products): array
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

    public function getExcessiveStockProducts($products): array
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

    public function getDetailedMetrics(): array
    {
        return Product::select('id', 'name', 'min_stock_level', 'max_stock_level', 'category_id')
            ->with('category')
            ->get()
            ->groupBy('category')
            ->map(function ($products) {
                $categoryId = $products->first()->category->id;
                $categoryName = $products->first()->category->name;

                return [
                    'category' => [
                        'id' => $categoryId,
                        'name' => $categoryName,
                    ],
                    'products' => $products->map(function ($product) {
                        $stock_balance = $product->stock_balance;

                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'min' => $product->min_stock_level,
                            'max' => $product->max_stock_level,
                            'current' => $stock_balance,
                            'range' => $product->max_stock_level - $product->min_stock_level,
                            'status' => $this->getStockStatus($stock_balance, $product->min_stock_level, $product->max_stock_level),
                        ];
                    }),
                ];
            })->values()->toArray();
    }

    public function getStockStatus($balance, $min, $max): string
    {
        if ($balance < $min) {
            return 'understocked';
        } elseif ($balance > $max) {
            return 'overstocked';
        } else {
            return 'within_range';
        }
    }
}
