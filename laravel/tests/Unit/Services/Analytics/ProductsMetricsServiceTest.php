<?php

namespace Tests\Unit\Services\Analytics;
namespace Tests\Unit\Services\Analytics;

use App\Models\Product;
use App\Services\Analytics\ProductsMetricsService;
use App\Traits\OrderCreation;
use App\Traits\SaleCreation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductsMetricsServiceTest extends TestCase
{
    use DatabaseTransactions, SaleCreation, OrderCreation;

    private ProductsMetricsService $service;
    private Product $product1;
    private Product $product2;
    private Product $product3;
    private Product $product4;
    private Product $product5;
    private string $startDate;
    private string $endDate;

    /**
     * Setup necessary data for testing the product metrics service.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ProductsMetricsService();

        $this->product1 = Product::factory()->create([
            'sale' => 2000,
        ]);
        $this->product2 = Product::factory()->create([
            'sale' => 5000,
        ]);
        $this->product3 = Product::factory()->create([
            'sale' => 3000,
        ]);
        $this->product4 = Product::factory()->create([
            'sale' => 1000,
        ]);
        $this->product5 = Product::factory()->create([
            'sale' => 7000,
        ]);
        $this->product6 = Product::factory()->create([
            'sale' => 6000,
        ]);

        $this->startDate = now()->subDays(10)->toDateString();
        $this->endDate = now()->toDateString();
    }

    /**
     * Test the `getTopSellingProducts` method to ensure it returns the correct top sellers.
     */
    public function testGetTopSellingProducts(): void
    {
        $products = collect([]);
        $topSellers = $this->service->getTopSellingProducts($products);
        $this->assertEmpty($topSellers);

        $products = collect([$this->product1, $this->product2, $this->product3, $this->product4, $this->product5, $this->product6]);

        $this->createSale($products, [1, 2, 10, 10, 10, 10], $this->startDate, $this->endDate);
        $topSellers = $this->service->getTopSellingProducts($products);
        $this->assertCount(5, $topSellers);
        $topSellerIds = array_column($topSellers, 'id');
        $this->assertContains($this->product6->id, $topSellerIds);
        $this->assertContains($this->product5->id, $topSellerIds);
        $this->assertContains($this->product4->id, $topSellerIds);
        $this->assertContains($this->product3->id, $topSellerIds);
        $this->assertContains($this->product2->id, $topSellerIds);
        $this->assertNotContains($this->product1->id, $topSellerIds);

        $this->createSale(collect([$this->product1]), [2], $this->startDate, $this->endDate);
        $topSellers = $this->service->getTopSellingProducts($products);
        $topSellerIds = array_column($topSellers, 'id');
        $this->assertContains($this->product6->id, $topSellerIds);
        $this->assertContains($this->product5->id, $topSellerIds);
        $this->assertContains($this->product4->id, $topSellerIds);
        $this->assertContains($this->product3->id, $topSellerIds);
        $this->assertContains($this->product1->id, $topSellerIds);
        $this->assertNotContains($this->product2->id, $topSellerIds);

        $this->createSale(collect([$this->product2]), [2], $this->startDate, $this->endDate);
        $topSellers = $this->service->getTopSellingProducts($products);
        $topSellerIds = array_column($topSellers, 'id');
        $this->assertContains($this->product6->id, $topSellerIds);
        $this->assertContains($this->product5->id, $topSellerIds);
        $this->assertContains($this->product4->id, $topSellerIds);
        $this->assertContains($this->product3->id, $topSellerIds);
        $this->assertContains($this->product2->id, $topSellerIds);
        $this->assertNotContains($this->product1->id, $topSellerIds);
    }

    /**
     * Test the `getLeastSellingProducts` method to ensure it returns the correct least sellers.
     */
    public function testGetLeastSellingProducts(): void
    {
        $products = collect([]);
        $leastSellers = $this->service->getTopSellingProducts($products);
        $this->assertEmpty($leastSellers);

        $products = collect([$this->product1, $this->product2, $this->product3, $this->product4, $this->product5, $this->product6]);

        $this->createSale($products, [1, 2, 3, 4, 5, 10], $this->startDate, $this->endDate);
        $leastSellers = $this->service->getLeastSellingProducts($products);
        $this->assertCount(5, $leastSellers);
        $leastSellerIds = array_column($leastSellers, 'id');
        $this->assertContains($this->product1->id, $leastSellerIds);
        $this->assertContains($this->product2->id, $leastSellerIds);
        $this->assertContains($this->product3->id, $leastSellerIds);
        $this->assertContains($this->product4->id, $leastSellerIds);
        $this->assertContains($this->product5->id, $leastSellerIds);
        $this->assertNotContains($this->product6->id, $leastSellerIds);

        $this->createSale(collect([$this->product1]), [10], $this->startDate, $this->endDate);
        $leastSellers = $this->service->getLeastSellingProducts($products);
        $leastSellerIds = array_column($leastSellers, 'id');
        $this->assertContains($this->product2->id, $leastSellerIds);
        $this->assertContains($this->product3->id, $leastSellerIds);
        $this->assertContains($this->product4->id, $leastSellerIds);
        $this->assertContains($this->product5->id, $leastSellerIds);
        $this->assertContains($this->product6->id, $leastSellerIds);
        $this->assertNotContains($this->product1->id, $leastSellerIds);

        $this->createSale(collect([$this->product6]), [2], $this->startDate, $this->endDate);
        $leastSellers = $this->service->getLeastSellingProducts($products);
        $leastSellerIds = array_column($leastSellers, 'id');
        $this->assertContains($this->product1->id, $leastSellerIds);
        $this->assertContains($this->product2->id, $leastSellerIds);
        $this->assertContains($this->product3->id, $leastSellerIds);
        $this->assertContains($this->product4->id, $leastSellerIds);
        $this->assertContains($this->product5->id, $leastSellerIds);
        $this->assertNotContains($this->product6->id, $leastSellerIds);
    }

    /**
     * Test the `getHighestRevenueProducts` method to ensure it returns products with the highest revenue.
     */
    public function testGetHighestRevenueProducts(): void
    {
        $products = collect([]);
        $highestRevenueProducts = $this->service->getTopSellingProducts($products);
        $this->assertEmpty($highestRevenueProducts);

        $products = collect([$this->product1, $this->product2, $this->product3, $this->product4, $this->product5, $this->product6]);

        $this->createSale($products, [10, 1, 1, 10, 1, 1], $this->startDate, $this->endDate);
        $highestRevenueProducts = $this->service->getHighestRevenueProducts($products);
        $this->assertCount(5, $highestRevenueProducts);
        $highestRevenueProductIds = array_column($highestRevenueProducts, 'id');
        $this->assertContains($this->product1->id, $highestRevenueProductIds);
        $this->assertContains($this->product2->id, $highestRevenueProductIds);
        $this->assertContains($this->product4->id, $highestRevenueProductIds);
        $this->assertContains($this->product5->id, $highestRevenueProductIds);
        $this->assertContains($this->product6->id, $highestRevenueProductIds);
        $this->assertNotContains($this->product3->id, $highestRevenueProductIds);

        $this->createSale(collect([$this->product3]), [1], $this->startDate, $this->endDate);
        $highestRevenueProducts = $this->service->getHighestRevenueProducts($products);
        $highestRevenueProductIds = array_column($highestRevenueProducts, 'id');
        $this->assertContains($this->product1->id, $highestRevenueProductIds);
        $this->assertContains($this->product3->id, $highestRevenueProductIds);
        $this->assertContains($this->product4->id, $highestRevenueProductIds);
        $this->assertContains($this->product5->id, $highestRevenueProductIds);
        $this->assertContains($this->product6->id, $highestRevenueProductIds);
        $this->assertNotContains($this->product2->id, $highestRevenueProductIds);

        $this->createSale(collect([$this->product2, $this->product6]), [1, 1], $this->startDate, $this->endDate);
        $highestRevenueProducts = $this->service->getHighestRevenueProducts($products);
        $highestRevenueProductIds = array_column($highestRevenueProducts, 'id');
        $this->assertContains($this->product1->id, $highestRevenueProductIds);
        $this->assertContains($this->product2->id, $highestRevenueProductIds);
        $this->assertContains($this->product4->id, $highestRevenueProductIds);
        $this->assertContains($this->product5->id, $highestRevenueProductIds);
        $this->assertContains($this->product6->id, $highestRevenueProductIds);
        $this->assertNotContains($this->product3->id, $highestRevenueProductIds);
    }

    /**
     * Test the `getLowestRevenueProducts` method to ensure it returns products with the lowest revenue.
     */
    public function testGetLowestRevenueProducts(): void
    {
        $products = collect([]);
        $lowestRevenueProducts = $this->service->getTopSellingProducts($products);
        $this->assertEmpty($lowestRevenueProducts);

        $products = collect([$this->product1, $this->product2, $this->product3, $this->product4, $this->product5, $this->product6]);

        $this->createSale($products, [1, 1, 1, 10, 1, 1], $this->startDate, $this->endDate);
        $lowestRevenueProducts = $this->service->getLowestRevenueProducts($products);
        $this->assertCount(5, $lowestRevenueProducts);
        $lowestRevenueProductIds = array_column($lowestRevenueProducts, 'id');
        $this->assertContains($this->product1->id, $lowestRevenueProductIds);
        $this->assertContains($this->product2->id, $lowestRevenueProductIds);
        $this->assertContains($this->product3->id, $lowestRevenueProductIds);
        $this->assertContains($this->product5->id, $lowestRevenueProductIds);
        $this->assertContains($this->product6->id, $lowestRevenueProductIds);
        $this->assertNotContains($this->product4->id, $lowestRevenueProductIds);

        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->endDate);
        $lowestRevenueProducts = $this->service->getLowestRevenueProducts($products);
        $lowestRevenueProductIds = array_column($lowestRevenueProducts, 'id');
        $this->assertContains($this->product2->id, $lowestRevenueProductIds);
        $this->assertContains($this->product3->id, $lowestRevenueProductIds);
        $this->assertContains($this->product4->id, $lowestRevenueProductIds);
        $this->assertContains($this->product5->id, $lowestRevenueProductIds);
        $this->assertContains($this->product6->id, $lowestRevenueProductIds);
        $this->assertNotContains($this->product1->id, $lowestRevenueProductIds);

        $this->createSale(collect([$this->product4]), [3], $this->startDate, $this->endDate);
        $lowestRevenueProducts = $this->service->getLowestRevenueProducts($products);
        $lowestRevenueProductIds = array_column($lowestRevenueProducts, 'id');
        $this->assertContains($this->product1->id, $lowestRevenueProductIds);
        $this->assertContains($this->product2->id, $lowestRevenueProductIds);
        $this->assertContains($this->product3->id, $lowestRevenueProductIds);
        $this->assertContains($this->product5->id, $lowestRevenueProductIds);
        $this->assertContains($this->product6->id, $lowestRevenueProductIds);
        $this->assertNotContains($this->product4->id, $lowestRevenueProductIds);
    }

    /**
     * Test the `calculateTotalQuantitySold` method to ensure it correctly calculates the quantity sold for a product.
     */
    public function testCalculateTotalQuantitySold(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $this->createSale($products, [10, 5], $this->startDate, $this->endDate);

        $quantitySold1 = $this->service->calculateTotalQuantitySold($this->product1);
        $quantitySold2 = $this->service->calculateTotalQuantitySold($this->product2);

        $this->assertEquals(10, $quantitySold1);
        $this->assertEquals(5, $quantitySold2);
    }

    /**
     * Test the `calculateTotalSalesRevenue` method to ensure it correctly calculates the total sales revenue for a product.
     */
    public function testCalculateTotalSalesRevenue(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $this->createSale($products, [10, 5], $this->startDate, $this->endDate);

        $revenue1 = $this->service->calculateTotalSalesRevenue($this->product1);
        $revenue2 = $this->service->calculateTotalSalesRevenue($this->product2);

        $this->assertEquals(200.00, $revenue1);
        $this->assertEquals(250.00, $revenue2);
    }

    /**
     * Test the `getStockBalanceAt` method to ensure it returns the correct stock balance at a given date.
     */
    public function testGetStockBalanceAt(): void
    {
        $balanceAtStart = $this->service->getStockBalanceAt($this->product1, $this->startDate);
        $this->assertEquals(0, $balanceAtStart);

        $this->createOrder(collect([$this->product1]), [10], $this->startDate, $this->startDate);
        $balanceAfterOrder = $this->service->getStockBalanceAt($this->product1, $this->startDate);
        $this->assertEquals(10, $balanceAfterOrder);

        $saleDate = now()->subDays(9)->toDateString();
        $this->createSale(collect([$this->product1]), [4], $saleDate, $saleDate);
        $balanceAfterSale = $this->service->getStockBalanceAt($this->product1, $saleDate);
        $this->assertEquals(6, $balanceAfterSale);

        $laterDate = now()->subDays(5)->toDateString();
        $balanceLater = $this->service->getStockBalanceAt($this->product1, $laterDate);
        $this->assertEquals(6, $balanceLater);

        $finalDate = now()->subDays(3)->toDateString();
        $this->createOrder(collect([$this->product1]), [5], $finalDate, $finalDate);
        $finalBalance = $this->service->getStockBalanceAt($this->product1, $finalDate);
        $this->assertEquals(11, $finalBalance);
    }

    /**
     * Test the `calculateProductQuantitySold` method to ensure it calculates the correct quantity sold on a specific date.
     */
    public function testCalculateProductQuantitySold(): void
    {
        $saleDate1 = now()->subDays(5)->toDateString();
        $saleDate2 = now()->subDays(3)->toDateString();

        $quantitySold = $this->service->calculateProductQuantitySold($this->product1, $saleDate1);
        $this->assertEquals(0, $quantitySold);
        $quantitySold = $this->service->calculateProductQuantitySold($this->product1, $saleDate2);
        $this->assertEquals(0, $quantitySold);
        $quantitySold = $this->service->calculateProductQuantitySold($this->product2, $saleDate1);
        $this->assertEquals(0, $quantitySold);
        $quantitySold = $this->service->calculateProductQuantitySold($this->product2, $saleDate2);
        $this->assertEquals(0, $quantitySold);

        $this->createSale(collect([$this->product1]), [5], $saleDate1, $saleDate1);
        $this->createSale(collect([$this->product1]), [3], $saleDate2, $saleDate2);
        $quantitySoldOnDate1 = $this->service->calculateProductQuantitySold($this->product1, $saleDate1);
        $quantitySoldOnDate2 = $this->service->calculateProductQuantitySold($this->product1, $saleDate2);
        $this->assertEquals(5, $quantitySoldOnDate1);
        $this->assertEquals(3, $quantitySoldOnDate2);
    }

    /**
     * Test the `calculateProductSalesRevenue` method to ensure it calculates the correct sales revenue on a specific date.
     */
    public function testCalculateDailyProductSalesRevenue(): void
    {
        $saleDate1 = now()->subDays(7)->toDateString();
        $saleDate2 = now()->subDays(4)->toDateString();

        $salesRevenue = $this->service->calculateProductSalesRevenue($this->product1, $saleDate1);
        $this->assertEquals(0.00, $salesRevenue);
        $salesRevenue = $this->service->calculateProductSalesRevenue($this->product1, $saleDate2);
        $this->assertEquals(0.00, $salesRevenue);
        $salesRevenue = $this->service->calculateProductSalesRevenue($this->product2, $saleDate1);
        $this->assertEquals(0.00, $salesRevenue);
        $salesRevenue = $this->service->calculateProductSalesRevenue($this->product2, $saleDate2);
        $this->assertEquals(0.00, $salesRevenue);

        $this->createSale(collect([$this->product2]), [2], $saleDate1, $saleDate1);
        $this->createSale(collect([$this->product2]), [4], $saleDate2, $saleDate2);

        $revenueOnDate1 = $this->service->calculateProductSalesRevenue($this->product2, $saleDate1);
        $revenueOnDate2 = $this->service->calculateProductSalesRevenue($this->product2, $saleDate2);

        $this->assertEquals(100.00, $revenueOnDate1);
        $this->assertEquals(200.00, $revenueOnDate2);
    }
}
