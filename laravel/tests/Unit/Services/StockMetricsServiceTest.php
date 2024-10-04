<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Services\DescriptiveAnalytics\StockMetricsService;
use App\Traits\OrderCreation;
use App\Traits\SaleCreation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMetricsServiceTest extends TestCase
{
    use RefreshDatabase, SaleCreation, OrderCreation;

    private StockMetricsService $service;
    private Product $product1;
    private Product $product2;
    private string $startDate;
    private string $endDate;
    private Store $store;

    /**
     * Setup necessary data for testing the stock metrics service.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Instantiate the service
        $this->service = new StockMetricsService();

        // Create elements needed throughout
        $this->store = Store::factory()->create();

        $category1 = Category::factory()->create(['name' => 'Category 1']);
        $category2 = Category::factory()->create(['name' => 'Category 2']);

        $this->product1 = Product::factory()->create([
            'category_id' => $category1->id,
            'cost' => 1000,
            'min_stock_level' => 15,
            'max_stock_level' => 10,
        ]);
        $this->product2 = Product::factory()->create([
            'category_id' => $category2->id,
            'cost' => 5000,
            'min_stock_level' => 1,
            'max_stock_level' => 10,
        ]);

        $this->startDate = now()->subDays(10)->toDateString();
        $this->endDate = now()->toDateString();
    }

    /**
     * Test the `getOverviewMetrics` which covers the correct calculations of stock metrics in `calculateStockMetrics`.
     */
    public function testGetOverviewMetrics(): void
    {
        // Simulate orders and sales
        $this->createOrder(collect([$this->product1]), [20], $this->startDate, $this->endDate);
        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->endDate);
        $this->createOrder(collect([$this->product2]), [10], $this->startDate, $this->endDate);
        $this->createSale(collect([$this->product2]), [10], $this->startDate, $this->endDate);

        // Run service function
        $metrics = $this->service->getOverviewMetrics();

        // Assert metrics
        $this->assertEquals(1, $metrics['products_in_stock_count']);
        $this->assertEquals(1, $metrics['products_out_of_stock_count']);
        $this->assertEquals($this->product2->id, $metrics['low_stock_products'][0]['id']);
        $this->assertEquals($this->product1->id, $metrics['excessive_stock_products'][0]['id']);
        $this->assertEquals(2, $metrics['product_count']);
        $this->assertEquals(150.00, $metrics['inventory_value']);
        $this->assertEquals(15, $metrics['total_items_in_stock']);
    }

    /**
     * Test the `getDetailedMetrics` which covers the correct mappings of products and their details in `mapProducts` and `mapProductDetails`.
     */
    public function testGetDetailedMetrics(): void
    {
        // Step 1: Simulate an order and sale for product1
        $this->createOrder(collect([$this->product1]), [20], $this->startDate, $this->endDate);  // Add 20 units
        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->endDate);    // Sell 5 units

        // Step 2: Call the service to get detailed metrics for the category of product1
        $detailedMetrics = $this->service->getDetailedMetrics($this->product1->category);

        // Step 3: Assertions
        // Assert category details
        $this->assertEquals($this->product1->category->id, $detailedMetrics['category']['id']);
        $this->assertEquals($this->product1->category->name, $detailedMetrics['category']['name']);

        // Assert product data for product1
        $this->assertCount(1, $detailedMetrics['products']);  // Only one product in this category
        $product = $detailedMetrics['products'][0];
        $this->assertEquals($this->product1->id, $product['id']);
        $this->assertEquals($this->product1->name, $product['name']);
        $this->assertEquals(15, $product['current']);  // Final stock balance after transactions
        $this->assertEquals('overstocked', $product['status']);  // Stock is within range
    }

    /**
     * Test the `getStockStatus` method to ensure correct stock status determination.
     */
    public function testGetStockStatus(): void
    {
        // Assert understocked
        $status = $this->service->getStockStatus(2, 5, 15);
        $this->assertEquals('understocked', $status);

        // Assert overstocked
        $status = $this->service->getStockStatus(20, 5, 15);
        $this->assertEquals('overstocked', $status);

        // Assert within range
        $status = $this->service->getStockStatus(10, 5, 15);
        $this->assertEquals('within_range', $status);
    }


}
