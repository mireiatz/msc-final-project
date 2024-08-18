<?php

namespace Tests\Unit\Services\Analytics;

use App\Models\Product;
use App\Models\Sale;
use App\Services\Analytics\SalesMetricsService;
use App\Traits\SaleCreation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SalesMetricsServiceTest extends TestCase
{
    use DatabaseTransactions, SaleCreation;

    private SalesMetricsService $service;
    private Product $product1;
    private Product $product2;
    private string $startDate;
    private string $endDate;


    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SalesMetricsService();

        $this->product1 = Product::factory()->create([
            'sale' => 2000,
        ]);
        $this->product2 = Product::factory()->create([
            'sale' => 5000,
        ]);

        $this->startDate = now()->subDays(10)->toDateString();
        $this->endDate = now()->toDateString();
    }

    private function getSalesData(): Collection
    {
        $productIds = [$this->product1->id, $this->product2->id];

        return Sale::whereBetween('sales.date', [$this->startDate, $this->endDate])
            ->with('products')
            ->whereHas('products', function ($q) use ($productIds) {
                $q->whereIn('products.id', $productIds);
            })
            ->get();
    }

    public function testCountSales(): void
    {
        $sales = $this->getSalesData();
        $this->assertEquals(0, $this->service->countSales($sales));

        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(1, $this->service->countSales($sales));

        $this->createSale(collect([$this->product2]), [3], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(2, $this->service->countSales($sales));
    }

    public function testGetHighestSale(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $sales = $this->getSalesData();
        $this->assertEquals(0, $this->service->getHighestSale($sales));

        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(100.00, $this->service->getHighestSale($sales));

        $this->createSale($products, [1, 1], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(100.00, $this->service->getHighestSale($sales));

        $this->createSale(collect([$this->product2]), [10], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(500.00, $this->service->getHighestSale($sales));

        $this->createSale($products, [10, 6], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(500.00, $this->service->getHighestSale($sales));
    }

    public function testGetLowestSale(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $sales = $this->getSalesData();
        $this->assertEquals(0, $this->service->getLowestSale($sales));

        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(100.00, $this->service->getLowestSale($sales));

        $this->createSale(collect([$this->product2]), [5], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(100.00, $this->service->getLowestSale($sales));

        $this->createSale(collect([$this->product1]), [2], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(40.00, $this->service->getLowestSale($sales));

        $this->createSale(collect([$this->product2]), [1], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(40.00, $this->service->getLowestSale($sales));

        $this->createSale($products, [1, 1], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(40.00, $this->service->getLowestSale($sales));
    }

    public function testCalculateTotalItemsSold(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $sales = $this->getSalesData();
        $this->assertEquals(0, $this->service->calculateTotalItemsSold($sales));

        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(5, $this->service->calculateTotalItemsSold($sales));

        $this->createSale(collect([$this->product2]), [3], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(8, $this->service->calculateTotalItemsSold($sales));

        $this->createSale($products, [10, 1], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(19, $this->service->calculateTotalItemsSold($sales));
    }

    public function testCalculateTotalSalesValue(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $sales = $this->getSalesData();
        $this->assertEquals(0, $this->service->calculateTotalSalesValue($sales));

        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(100.00, $this->service->calculateTotalSalesValue($sales));

        $this->createSale(collect([$this->product2]), [4], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(300.00, $this->service->calculateTotalSalesValue($sales));

        $this->createSale($products, [10, 20], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(1500.00, $this->service->calculateTotalSalesValue($sales));
    }

    public function testGetMaxItemsSoldInSale(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $sales = $this->getSalesData();
        $this->assertEquals(0, $this->service->getMaxItemsSoldInSale($sales));

        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(5, $this->service->getMaxItemsSoldInSale($sales));

        $this->createSale(collect([$this->product2]), [10], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(10, $this->service->getMaxItemsSoldInSale($sales));

        $this->createSale($products, [10, 5], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(15, $this->service->getMaxItemsSoldInSale($sales));

        $this->createSale($products, [1, 5], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(15, $this->service->getMaxItemsSoldInSale($sales));
    }

    public function testGetMinItemsSoldInSale(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $sales = $this->getSalesData();
        $this->assertEquals(0, $this->service->getMinItemsSoldInSale($sales));

        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(5, $this->service->getMinItemsSoldInSale($sales));

        $this->createSale(collect([$this->product1]), [2], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(2, $this->service->getMinItemsSoldInSale($sales));

        $this->createSale($products, [2, 1], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(2, $this->service->getMinItemsSoldInSale($sales));

        $this->createSale($products, [1, 1], $this->startDate, $this->endDate);
        $sales = $this->getSalesData();
        $this->assertEquals(2, $this->service->getMinItemsSoldInSale($sales));
    }
}
