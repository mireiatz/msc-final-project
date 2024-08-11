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
        $productsInStockCount = $this->calculateProductsInStockCount($products);
        $productsOutOfStockCount = $this->calculateProductsOutOfStockCount($products);
        $criticallyLowStockProducts = $this->findCriticallyLowStockProducts($products);
        $excessiveStockProducts = $this->findExcessiveStockProducts($products);
        $inventoryValue = $this->calculateInventoryValue($products);

        return [
            'products_in_stock_count' => $productsInStockCount,
            'products_out_of_stock_count' => $productsOutOfStockCount,
            'critically_low_stock_products' => $criticallyLowStockProducts,
            'excessive_stock_products' => $excessiveStockProducts,
            'inventory_value' => $inventoryValue / 100,
        ];
    }

    private function calculateProductsInStockCount($products): int
    {
        return $products->filter(function ($product) {
            return $product->stock_balance > 0;
        })->count();
    }

    private function calculateProductsOutOfStockCount($products): int
    {
        return $products->filter(function ($product) {
            return $product->stock_balance <= 0;
        })->count();
    }

    private function findCriticallyLowStockProducts($products): array
    {
        return $products->filter(function ($product) {
            return $product->stock_balance < $product->min_stock_level;
        })->pluck('name')->toArray();
    }

    private function findExcessiveStockProducts($products): array
    {
        return $products->filter(function ($product) {
            return $product->stock_balance > $product->max_stock_level;
        })->pluck('name')->toArray();
    }

    private function calculateInventoryValue($products): int
    {
        return $products->reduce(function ($carry, $product) {
            $stockBalance = $product->inventoryTransactions()->sum('quantity');
            $productValue = $stockBalance * $product->cost;

            return $carry + $productValue;
        }, 0.0);
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
                        $stock_balance = $product->inventoryTransactions()->sum('quantity');

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

    private function getStockStatus($balance, $min, $max): string
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
