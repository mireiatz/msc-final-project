<?php

namespace Tests\Unit\Services\Analytics;
namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Store;
use App\Services\DescriptiveAnalytics\ProductsMetricsService;
use App\Traits\OrderCreation;
use App\Traits\SaleCreation;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductsMetricsServiceTest extends TestCase
{
    use RefreshDatabase, SaleCreation, OrderCreation;

    private ProductsMetricsService $service;
    private Product $product1;
    private Product $product2;
    private Product $product3;
    private Product $product4;
    private Product $product5;
    private string $startDate;
    private string $endDate;
    private Category $category;
    private Store $store;

    /**
     * Setup necessary data for testing the product metrics service.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Instantiate the service
        $this->service = new ProductsMetricsService();

        // Create elements needed throughout
        $this->store = Store::factory()->create();
        Provider::factory()->create();
        $this->category = Category::factory()->create();

        $products = collect([
            $this->product1 = Product::factory()->create([
                'sale' => 2000,
            ]),
            $this->product2 = Product::factory()->create([
                'sale' => 5000,
            ]),
            $this->product3 = Product::factory()->create([
                'sale' => 3000,
            ]),
            $this->product4 = Product::factory()->create([
                'sale' => 1000,
            ]),
            $this->product5 = Product::factory()->create([
                'sale' => 7000,
            ]),
            $this->product6 = Product::factory()->create([
                'sale' => 6000,
            ])
        ]);

        $products->each(function ($product) {
            $product->update(['category_id' => $this->category->id]);
        });

        $this->startDate = now()->subDays(10)->toDateString();
        $this->endDate = now()->toDateString();
    }

    /**
     * Test the `getOverviewMetrics` method to ensure it returns the correct metrics for top-selling, least-selling, highest-revenue, and lowest-revenue products.
     */
    public function testGetOverviewMetrics(): void
    {
        $products = collect([$this->product1, $this->product2, $this->product3, $this->product4, $this->product5, $this->product6]);

        // Simulate product sales
        $this->createSale($products, [10, 5, 3, 1, 8, 7], $this->startDate, $this->endDate);

        // Run function in service
        $metrics = $this->service->getOverviewMetrics($this->startDate, $this->endDate);

        // Assertions for top-selling products
        $topSellers = $metrics['top_selling_products'];
        $this->assertCount(5, $topSellers);
        $topSellerIds = array_column($topSellers, 'id');
        $this->assertContains($this->product1->id, $topSellerIds);
        $this->assertContains($this->product5->id, $topSellerIds);

        // Assertions for least-selling products
        $leastSellers = $metrics['least_selling_products'];
        $this->assertCount(5, $leastSellers);
        $leastSellerIds = array_column($leastSellers, 'id');
        $this->assertContains($this->product4->id, $leastSellerIds);

        // Assertions for highest-revenue products
        $highestRevenueProducts = $metrics['highest_revenue_products'];
        $this->assertCount(5, $highestRevenueProducts);
        $highestRevenueProductIds = array_column($highestRevenueProducts, 'id');
        $this->assertContains($this->product1->id, $highestRevenueProductIds);

        // Assertions for lowest-revenue products
        $lowestRevenueProducts = $metrics['lowest_revenue_products'];
        $this->assertCount(5, $lowestRevenueProducts);
        $lowestRevenueProductIds = array_column($lowestRevenueProducts, 'id');
        $this->assertContains($this->product4->id, $lowestRevenueProductIds);
    }

    /**
     * Test the `getDetailedMetrics` method to ensure it returns the correct metrics for products in a category.
     */
    public function testGetDetailedMetrics(): void
    {
        $products = collect([$this->product1, $this->product2, $this->product3]);

        // Simulate sales
        $this->createSale($products, [10, 5, 3], $this->startDate, $this->endDate);

        // Run function in service
        $detailedMetrics = $this->service->getDetailedMetrics($this->category, $this->startDate, $this->endDate);

        // Assert the detailed metrics values
        $this->assertEquals($this->product1->id, $detailedMetrics[0]['id']);
        $this->assertEquals(10, $detailedMetrics[0]['total_quantity_sold']);
    }

    /**
     * Test the `getProductsByCategory` method to ensure it returns the correct products within a category.
     */
    public function testGetProductsByCategory(): void
    {
        $category = Category::factory()->create();
        $products = collect([$this->product1, $this->product2]);

        // Assign category to products
        $products->each(function ($product) use ($category) {
            $product->update(['category_id' => $category->id]);
        });

        // Run function in service
        $categoryProducts = $this->service->getProductsByCategory($category, $this->startDate, $this->endDate);

        // Assert the products are returned correctly
        $this->assertCount(2, $categoryProducts);
        $this->assertEquals($this->product1->id, $categoryProducts->first()->id);
        $this->assertEquals($this->product2->id, $categoryProducts->last()->id);
    }


    /**
     * Test the `getProductSalesMetrics` method to ensure it returns the correct quantity and revenue for a product.
     */
    public function testGetProductSalesMetrics(): void
    {
        $this->createSale(collect([$this->product1]), [10], $this->startDate, $this->endDate);

        // Run function in service
        $salesMetrics = $this->service->getProductSalesMetrics($this->product1->id, $this->startDate, $this->endDate);

        // Assert the sales metrics
        $this->assertEquals(10, $salesMetrics['total_quantity_sold']);
        $this->assertEquals(200.00, $salesMetrics['total_sales_revenue']);
    }

    /**
     * Test the `getProductStockBalances` method to ensure it returns the correct stock balances.
     */
    public function testGetProductStockBalances(): void
    {
        // Simulate transactions
        InventoryTransaction::create([
            'parent_type' => 'App\Models\OrderProduct',
            'parent_id' => 'id',
            'store_id' => $this->store->id,
            'product_id' => $this->product1->id,
            'date' => Carbon::parse($this->startDate)->subDays(5),
            'quantity' => 20,
            'stock_balance' => $this->product1->stock_balance + 20,
        ]);
        InventoryTransaction::create([
            'parent_type' => 'App\Models\SaleProduct',
            'parent_id' => 'id',
            'store_id' => $this->store->id,
            'product_id' => $this->product1->id,
            'date' => $this->endDate,
            'quantity' => 5,
            'stock_balance' => 5,
        ]);

        // Run function in service
        $stockBalances = $this->service->getProductStockBalances($this->product1, $this->startDate, $this->endDate);

        // Assert stock balances
        $this->assertEquals(20, $stockBalances['initial_stock_balance']);
        $this->assertEquals(5, $stockBalances['final_stock_balance']);
    }

    /**
     * Test the `getStockBalanceInRange` method to ensure it returns the correct stock balance over a date range.
     */
    public function testGetStockBalanceInRange(): void
    {
        // Simulate transactions
        InventoryTransaction::create([
            'parent_type' => 'App\Models\OrderProduct',
            'parent_id' => 'id',
            'store_id' => $this->store->id,
            'product_id' => $this->product1->id,
            'date' => $this->startDate, // Before the start date
            'quantity' => 20,
            'stock_balance' => $this->product1->stock_balance + 20,
        ]);

        // Run function in service
        $stockBalances = $this->service->getStockBalanceInRange($this->product1, $this->startDate, $this->endDate);

        // Assert stock balances for each date
        $this->assertArrayHasKey($this->startDate, $stockBalances);
        $this->assertEquals(20, $stockBalances[$this->startDate]);
    }

    /**
     * Test the `getProductSpecificMetrics` method to ensure it returns the correct metrics for a product over a date range.
     * @throws Exception
     */
    public function testGetProductSpecificMetrics(): void
    {
        // Simulate sales on different days
        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->startDate);
        $this->createSale(collect([$this->product1]), [3], $this->endDate, $this->endDate);

        // Run function in service
        $specificMetrics = $this->service->getProductSpecificMetrics($this->product1, $this->startDate, $this->endDate);

        // Assert metrics for quantity sold
        $quantitySold = $specificMetrics['quantity_sold'];
        $this->assertEquals(5, $quantitySold[0]['amount']);

        // Assert metrics for sales revenue
        $salesRevenue = $specificMetrics['sales_revenue'];
        $this->assertEquals(100.00, $salesRevenue[0]['amount']);
    }
}
