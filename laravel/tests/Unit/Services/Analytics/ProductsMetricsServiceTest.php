<?php

namespace Tests\Unit\Services\Analytics;
namespace Tests\Unit\Services\Analytics;

use App\Models\Product;
use App\Services\Analytics\ProductsMetricsService;
use App\Traits\SaleCreation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductsMetricsServiceTest extends TestCase
{
    use DatabaseTransactions, SaleCreation;

    private ProductsMetricsService $service;
    private Product $product1;
    private Product $product2;
    private Product $product3;
    private Product $product4;
    private Product $product5;
    private string $startDate;
    private string $endDate;

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
}
