<?php

namespace Tests\Unit\Services\Analytics;

use App\Models\Category;
use App\Models\Product;
use App\Services\Analytics\StockMetricsService;
use App\Traits\OrderCreation;
use App\Traits\SaleCreation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StockMetricsServiceTest extends TestCase
{
    use DatabaseTransactions, SaleCreation, OrderCreation;

    private StockMetricsService $service;
    private Product $product1;
    private Product $product2;
    private string $startDate;
    private string $endDate;

    /**
     * Setup necessary data for testing the stock metrics service.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new StockMetricsService();

        $category1 = Category::factory()->create(['name' => 'Category 1']);
        $category2 = Category::factory()->create(['name' => 'Category 2']);

        $this->product1 = Product::factory()->create([
            'category_id' => $category1->id,
            'cost' => 1000,
            'min_stock_level' => 5,
            'max_stock_level' => 15,
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
     * Test the `calculateInventoryValue` method to ensure it calculates the correct total value of inventory.
     */
    public function testCalculateInventoryValue(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $inventoryValue = $this->service->calculateInventoryValue($products);
        $this->assertEquals(0, $inventoryValue);

        $this->createOrder($products, [1, 1], $this->startDate, $this->endDate);
        $inventoryValue = $this->service->calculateInventoryValue($products);
        $this->assertEquals(60.00, $inventoryValue);

        $this->createOrder($products, [2, 10], $this->startDate, $this->endDate);
        $inventoryValue = $this->service->calculateInventoryValue($products);
        $this->assertEquals(580.00, $inventoryValue);

        $this->createSale($products, [1, 1], $this->startDate, $this->endDate);
        $inventoryValue = $this->service->calculateInventoryValue($products);
        $this->assertEquals(520.00, $inventoryValue);

        $this->createSale($products, [2, 10], $this->startDate, $this->endDate);
        $inventoryValue = $this->service->calculateInventoryValue($products);
        $this->assertEquals(0, $inventoryValue);

        $this->createOrder(collect([$this->product1]), [10], $this->startDate, $this->endDate);
        $inventoryValue = $this->service->calculateInventoryValue($products);
        $this->assertEquals(100.00, $inventoryValue);

        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->endDate);
        $inventoryValue = $this->service->calculateInventoryValue($products);
        $this->assertEquals(50.00, $inventoryValue);

        $this->createOrder(collect([$this->product2]), [10], $this->startDate, $this->endDate);
        $inventoryValue = $this->service->calculateInventoryValue($products);
        $this->assertEquals(550.00, $inventoryValue);

        $this->createSale(collect([$this->product2]), [5], $this->startDate, $this->endDate);
        $inventoryValue = $this->service->calculateInventoryValue($products);
        $this->assertEquals(300.00, $inventoryValue);
    }

    /**
     * Test the `calculateTotalItemsInStock` method to ensure it calculates the correct total number of items in stock.
     */
    public function testCalculateTotalItemsInStock(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $totalItems = $this->service->calculateTotalItemsInStock($products);
        $this->assertEquals(0, $totalItems);

        $this->createOrder($products, [5, 10], $this->startDate, $this->endDate);
        $totalItems = $this->service->calculateTotalItemsInStock($products);
        $this->assertEquals(15, $totalItems);

        $this->createSale($products, [2, 3], $this->startDate, $this->endDate);
        $totalItems = $this->service->calculateTotalItemsInStock($products);
        $this->assertEquals(10, $totalItems);

        $this->createSale($products, [3, 7], $this->startDate, $this->endDate);
        $totalItems = $this->service->calculateTotalItemsInStock($products);
        $this->assertEquals(0, $totalItems);

        $this->createOrder(collect([$this->product1]), [100], $this->startDate, $this->endDate);
        $totalItems = $this->service->calculateTotalItemsInStock($products);
        $this->assertEquals(100, $totalItems);

        $this->createOrder(collect([$this->product2]), [10], $this->startDate, $this->endDate);
        $totalItems = $this->service->calculateTotalItemsInStock($products);
        $this->assertEquals(110, $totalItems);

        $this->createSale($products, [50, 10], $this->startDate, $this->endDate);
        $totalItems = $this->service->calculateTotalItemsInStock($products);
        $this->assertEquals(50, $totalItems);
    }

    /**
     * Test the `countProductsInStock` method to ensure it counts the correct number of products in stock.
     */
    public function testCountProductsInStock(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $productsInStockCount = $this->service->countProductsInStock($products);
        $this->assertEquals(0, $productsInStockCount);

        $this->createOrder($products, [10, 10], $this->startDate, $this->endDate);
        $productsInStockCount = $this->service->countProductsInStock($products);
        $this->assertEquals(2, $productsInStockCount);

        $this->createSale($products, [1, 1], $this->startDate, $this->endDate);
        $productsInStockCount = $this->service->countProductsInStock($products);
        $this->assertEquals(2, $productsInStockCount);

        $this->createSale($products, [1, 1], $this->startDate, $this->endDate);
        $productsInStockCount = $this->service->countProductsInStock($products);
        $this->assertEquals(2, $productsInStockCount);

        $this->createSale($products, [8, 1], $this->startDate, $this->endDate);
        $productsInStockCount = $this->service->countProductsInStock($products);
        $this->assertEquals(1, $productsInStockCount);

        $this->createSale(collect([$this->product2]), [7], $this->startDate, $this->endDate);
        $productsInStockCount = $this->service->countProductsInStock($products);
        $this->assertEquals(0, $productsInStockCount);
    }

    /**
     * Test the `countProductsOutOfStock` method to ensure it counts the correct number of products out of stock.
     */
    public function testCountProductsOutOfStock(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $productsOutOfStockCount = $this->service->countProductsOutOfStock($products);
        $this->assertEquals(2, $productsOutOfStockCount);

        $this->createOrder(collect([$this->product1]), [10], $this->startDate, $this->endDate);
        $productsOutOfStockCount = $this->service->countProductsOutOfStock($products);
        $this->assertEquals(1, $productsOutOfStockCount);

        $this->createOrder(collect([$this->product2]), [10], $this->startDate, $this->endDate);
        $productsOutOfStockCount = $this->service->countProductsOutOfStock($products);
        $this->assertEquals(0, $productsOutOfStockCount);

        $this->createSale(collect([$this->product1]), [8], $this->startDate, $this->endDate);
        $productsOutOfStockCount = $this->service->countProductsOutOfStock($products);
        $this->assertEquals(0, $productsOutOfStockCount);

        $this->createSale(collect([$this->product1]), [2], $this->startDate, $this->endDate);
        $productsOutOfStockCount = $this->service->countProductsOutOfStock($products);
        $this->assertEquals(1, $productsOutOfStockCount);

        $this->createSale(collect([$this->product2]), [5], $this->startDate, $this->endDate);
        $productsOutOfStockCount = $this->service->countProductsOutOfStock($products);
        $this->assertEquals(1, $productsOutOfStockCount);

        $this->createSale(collect([$this->product2]), [5], $this->startDate, $this->endDate);
        $productsOutOfStockCount = $this->service->countProductsOutOfStock($products);
        $this->assertEquals(2, $productsOutOfStockCount);

        $this->createOrder($products, [1, 1], $this->startDate, $this->endDate);
        $productsOutOfStockCount = $this->service->countProductsOutOfStock($products);
        $this->assertEquals(0, $productsOutOfStockCount);
    }

    /**
     * Test the `getLowStockProducts` method to ensure it returns products with stock below their minimum.
     */
    public function testGetLowStockProducts(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $lowStockProducts = $this->service->getLowStockProducts($products);
        $this->assertCount(2, $lowStockProducts);
        $this->assertEquals($this->product1->id, $lowStockProducts[0]['id']);
        $this->assertEquals($this->product2->id, $lowStockProducts[1]['id']);

        $this->createOrder(collect([$this->product1]), [4], $this->startDate, $this->endDate);
        $lowStockProducts = $this->service->getLowStockProducts($products);
        $this->assertCount(2, $lowStockProducts);
        $this->assertEquals($this->product1->id, $lowStockProducts[0]['id']);
        $this->assertEquals($this->product2->id, $lowStockProducts[1]['id']);

        $this->createOrder(collect([$this->product1]), [1], $this->startDate, $this->endDate);
        $lowStockProducts = $this->service->getLowStockProducts($products);
        $this->assertCount(1, $lowStockProducts);
        $this->assertEquals($this->product2->id, $lowStockProducts[0]['id']);

        $this->createOrder(collect([$this->product2]), [1], $this->startDate, $this->endDate);
        $lowStockProducts = $this->service->getLowStockProducts($products);
        $this->assertCount(0, $lowStockProducts);

        $this->createSale($products, [1, 1], $this->startDate, $this->endDate);
        $lowStockProducts = $this->service->getLowStockProducts($products);
        $this->assertCount(2, $lowStockProducts);
        $this->assertEquals($this->product1->id, $lowStockProducts[0]['id']);
        $this->assertEquals($this->product2->id, $lowStockProducts[1]['id']);
    }

    /**
     * Test the `getExcessiveStockProducts` method to ensure it returns products with stock above their maximum.
     */
    public function testGetExcessiveStockProducts(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $excessiveStockProducts = $this->service->getExcessiveStockProducts($products);
        $this->assertCount(0, $excessiveStockProducts);

        $this->createOrder(collect([$this->product1]), [15], $this->startDate, $this->endDate);
        $excessiveStockProducts = $this->service->getExcessiveStockProducts($products);
        $this->assertCount(0, $excessiveStockProducts);

        $this->createOrder(collect([$this->product1]), [1], $this->startDate, $this->endDate);
        $excessiveStockProducts = $this->service->getExcessiveStockProducts($products);
        $this->assertCount(1, $excessiveStockProducts);
        $this->assertEquals($this->product1->id, $excessiveStockProducts[0]['id']);

        $this->createOrder(collect([$this->product2]), [10], $this->startDate, $this->endDate);
        $excessiveStockProducts = $this->service->getExcessiveStockProducts($products);
        $this->assertCount(1, $excessiveStockProducts);

        $this->createOrder(collect([$this->product2]), [1], $this->startDate, $this->endDate);
        $excessiveStockProducts = $this->service->getExcessiveStockProducts($products);
        $this->assertCount(2, $excessiveStockProducts);
        $this->assertEquals($this->product1->id, $excessiveStockProducts[0]['id']);
        $this->assertEquals($this->product2->id, $excessiveStockProducts[1]['id']);

        $this->createSale($products, [1, 1], $this->startDate, $this->endDate);
        $excessiveStockProducts = $this->service->getExcessiveStockProducts($products);
        $this->assertCount(0, $excessiveStockProducts);
    }

    /**
     * Test the `getDetailedMetrics` method to ensure it returns the correct detailed stock metrics.
     */
    public function testGetDetailedMetrics(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $this->createOrder($products, [8, 8], $this->startDate, $this->endDate);
        $detailedMetrics = $this->service->mapProducts($products->groupBy('category_id'));
        $this->assertCount(2, $detailedMetrics);

        $category1Metrics = collect($detailedMetrics)->firstWhere('category.id', $this->product1->category_id);
        $this->assertNotNull($category1Metrics);
        $this->assertEquals($this->product1->category->name, $category1Metrics['category']['name']);
        $this->assertCount(1, $category1Metrics['products']);

        $product1Metrics = collect($category1Metrics['products'])->firstWhere('id', $this->product1->id);
        $this->assertNotNull($product1Metrics);
        $this->assertEquals($this->product1->name, $product1Metrics['name']);
        $this->assertEquals($this->product1->min_stock_level, $product1Metrics['min']);
        $this->assertEquals($this->product1->max_stock_level, $product1Metrics['max']);
        $this->assertEquals($this->product1->stock_balance, $product1Metrics['current']);
        $this->assertEquals('within_range', $product1Metrics['status']);

        $category2Metrics = collect($detailedMetrics)->firstWhere('category.id', $this->product2->category_id);
        $this->assertNotNull($category2Metrics);
        $this->assertEquals($this->product2->category->name, $category2Metrics['category']['name']);
        $this->assertCount(1, $category2Metrics['products']);

        $product2Metrics = collect($category2Metrics['products'])->firstWhere('id', $this->product2->id);
        $this->assertNotNull($product2Metrics);
        $this->assertEquals($this->product2->name, $product2Metrics['name']);
        $this->assertEquals($this->product2->min_stock_level, $product2Metrics['min']);
        $this->assertEquals($this->product2->max_stock_level, $product2Metrics['max']);
        $this->assertEquals($this->product2->stock_balance, $product2Metrics['current']);
        $this->assertEquals('within_range', $product2Metrics['status']);

        $this->createSale(collect([$this->product1]), [4], $this->startDate, $this->endDate);
        $this->createOrder(collect([$this->product2]), [8], $this->startDate, $this->endDate);
        $detailedMetrics = $this->service->mapProducts($products->groupBy('category_id'));

        $category1Metrics = collect($detailedMetrics)->firstWhere('category.id', $this->product1->category_id);
        $product1Metrics = collect($category1Metrics['products'])->firstWhere('id', $this->product1->id);
        $this->assertEquals($this->product1->stock_balance, $product1Metrics['current']);
        $this->assertEquals('understocked', $product1Metrics['status']);

        $category2Metrics = collect($detailedMetrics)->firstWhere('category.id', $this->product2->category_id);
        $product2Metrics = collect($category2Metrics['products'])->firstWhere('id', $this->product2->id);
        $this->assertEquals($this->product2->stock_balance, $product2Metrics['current']);
        $this->assertEquals('overstocked', $product2Metrics['status']);
    }
}
