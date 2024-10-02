<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SeedSalesFromFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $file;
    protected string $storeId;

    /**
     * Create a new job instance.
     *
     * @param string $file
     * @param string|null $storeId
     */
    public function __construct(string $file, ?string $storeId = null)
    {
        $this->file = $file;
        $this->storeId = $storeId ?? config('store.default.id');
    }

    /**
     * Execute the job.
     *
     * @throws Exception
     * @throws Throwable
     */
    public function handle(): void
    {
        // Ensure atomicity of order and sales transactions to maintain data consistency
        DB::beginTransaction();

        try {
            // Get records from the file
            $rows = parseCsvFile($this->file);

            // Filter out duplicate rows based on product name and category as a precaution
            $rows = $this->filterDuplicateRows($rows);

            // Calculate the start date of the week based on the year and week number
            $startDate = Carbon::now()->setISODate($rows[1]['year'], $rows[1]['week'])->startOfWeek();

            // First, restock products
            $this->restockProducts($rows, $startDate);

            // Convert weekly data into daily sales
            $dailySalesData = $this->pivotWeeklyToDaily($rows, $startDate);

            // For each day, generate random sales
            foreach ($dailySalesData as $date => $dailyProducts) {
                $this->simulateSales($dailyProducts, $date);
            }

            DB::commit(); // Commit the transaction after all operations succeed

            Log::info('SeedSalesFromFile job completed: ' . $this->file . ' file processed successfully');
        } catch (Throwable $e) {
            // Rollback the transaction if an error occurs
            DB::rollBack();

            Log::error('SeedSalesFromFile job failed | Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create orders and product orders as required.
     *
     * @param array $rows
     * @param
     */
    function restockProducts(array $rows, $startDate): void
    {
        // Collect unique product names and categories from rows
        $productNamesAndCategories = collect($rows)
            ->map(function ($row) {
                return [
                    'name' => $row['product_name'],
                    'category' => $row['category'],
                ];
            })
            ->unique();

        // Fetch products and manually key them by product name + category
        $products = Product::join('categories', 'products.category_id', '=', 'categories.id')
            ->where(function ($query) use ($productNamesAndCategories) {
                foreach ($productNamesAndCategories as $item) {
                    $query->orWhere(function ($subQuery) use ($item) {
                        $subQuery->where('products.name', $item['name'])
                            ->where('categories.name', $item['category']);
                    });
                }
            })
            ->select('products.*', 'categories.name as category_name')
            ->with('category')
            ->get()
            ->keyBy(function ($product) {
                return $product->name . ' ' . $product->category->name;
            });

        // Organise the products by provider
        $providerProducts = [];
        foreach ($rows as $row) {
            // Get the product and use it to set the provider
            $key = $row['product_name'] . ' ' . $row['category'];
            if (!isset($products[$key])) {
                Log::warning("Product not found: {$row['product_name']} in category {$row['category']}");
                continue;
            }
            $product = $products[$key];
            $providerId = $product->provider_id;

            // Group products by provider
            $providerProducts[$providerId][$product->id] = [
                'product' => $product,
                'quantity_needed' => $row['quantity'],
            ];
        }

        // Create a single order per provider
        foreach ($providerProducts as $providerId => $products) {
            $order = Order::create([
                'store_id' => $this->storeId,
                'provider_id' => $providerId,
                'date' => $startDate->copy()->subWeek(),
                'cost' => 0, // Updated during order products creation
                'currency' => 'gbp',
            ]);

            // Add products to the order if stock is insufficient
            foreach ($products as $productId => $data) {
                $product = $data['product'];
                $quantityNeeded = $data['quantity_needed'];

                // Check current stock
                $currentStock = $product->stock_balance ?? 0;

                // If stock is insufficient, create an order for restocking
                if ($currentStock < $quantityNeeded) {
                    $bufferStock = $quantityNeeded + rand(10, 100); // Add a buffer
                    $totalCost = $bufferStock * $product->cost;

                    // Attach product to the order
                    $order->products()->attach($productId, [
                        'quantity' => $bufferStock,
                        'unit_cost' => $product->cost,
                        'total_cost' => $totalCost,
                        'currency' => 'gbp',
                    ]);

                    // Update order total cost
                    $order->increment('cost', $totalCost);
                }
            }
        }
    }

    /**
     * Convert weekly sales data into daily data.
     *
     * @param array $rows
     * @param $startDate
     * @return array
     */
    protected function pivotWeeklyToDaily(array $rows, $startDate): array
    {
        $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $dailySalesData = [];

        // Loop through each product
        foreach ($rows as $row) {
            // Check that value and quantity columns exist
            if (empty($row['value']) || empty($row['quantity'])) {
                Log::warning("Missing 'value' or 'quantity' in row: " . json_encode($row));
                continue;
            }

            // Get product details
            $product = $this->findProduct($row['product_name'], $row['category']);
            if (!$product) {
                Log::warning("Product not found for row: " . json_encode($row));
                continue;
            }

            // Loop through each day and build the daily sales data
            foreach ($weekdays as $index => $day) {
                // Check the weekday columns exist
                if (!isset($row[$day])) {
                    Log::warning("Missing day value: {$day} for product: {$row['product_name']}");
                    continue;
                }
                $quantity = intval($row[$day]);

                if ($quantity > 0) {
                    $date = $startDate->copy()->addDays($index)->format('Y-m-d');
                    $dailySalesData[$date][] = [
                        'product' => $product,
                        'quantity' => $quantity,
                        'unit_price' => ($row['value'] / $row['quantity']) * 100, // Calculate sale price per unit in cents
                    ];
                }
            }
        }

        return $dailySalesData;
    }

    /**
     * Find the product by name and category.
     *
     * @param string $name
     * @param string $category
     * @return Product|null
     */
    protected function findProduct(string $name, string $category): ?Product
    {
        $category = Category::firstWhere('name', $category);

        return Product::where('name', $name)->where('category_id', $category->id ?? null)->first();
    }

    /**
     * Simulate sales for a given day.
     *
     * @param array $dailyProducts
     * @param string $date
     */
    protected function simulateSales(array $dailyProducts, string $date): void
    {
        // Create sales until there are no products to be "sold" left
        while (count($dailyProducts) > 0) {
            // Create the sale
            $sale = $this->createSale($date);

            // Randomly select up to 20 products or remaining products in the array
            $numberOfProductsLeft = min(count($dailyProducts), rand(1, 20));
            $productsInSale = array_splice($dailyProducts, 0, $numberOfProductsLeft);

            // Save a record of each product in each sale
            foreach ($productsInSale as $productData) {
                $this->createSaleProduct($sale, $productData['product'], $productData['quantity'], $productData['unit_price']);
            }
        }
    }

    /**
     * Create a sale for the day.
     *
     * @param string $date
     * @return Sale
     */
    protected function createSale(string $date): Sale
    {
        return Sale::create([
            'store_id' => $this->storeId,
            'date' => $date,
            'sale' => 0, // Values are calculated and updated after sale products creation
            'cost' => 0,
            'vat' => 0,
            'net_sale' => 0,
            'margin' => 0,
            'currency' => 'gbp',
        ]);
    }

    /**
     * Attach products to a sale and update sale total values.
     *
     * @param Sale $sale
     * @param Product $product
     * @param int $quantity
     * @param float $unitPrice
     */
    protected function createSaleProduct(Sale $sale, Product $product, int $quantity, float $unitPrice): void
    {
        $totalSale = $quantity * $unitPrice;
        $totalCost = $quantity * $product->cost;

        // Attach products to sale
        $sale->products()->attach($product->id, [
            'quantity' => $quantity,
            'unit_sale' => $unitPrice,
            'total_sale' => $totalSale,
            'unit_cost' => $product->cost,
            'total_cost' => $totalCost,
            'currency' => 'gbp',
        ]);

        // Update sale totals
        $sale->sale += $totalSale;
        $sale->cost += $totalCost;
        $sale->net_sale = $sale->sale + ($sale->sale * 0.2); // Set vat
        $sale->margin = $sale->sale - $sale->cost;
        $sale->save();
    }


    /**
     * Filter out duplicate rows based on product name and category.
     *
     * @param array $rows
     * @return array
     */
    protected function filterDuplicateRows(array $rows): array
    {
        return collect($rows)->unique(function ($row) {
            return $row['product_name'] . ' ' . $row['category'];
        })->toArray();
    }
}
