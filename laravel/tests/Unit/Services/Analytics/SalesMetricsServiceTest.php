<?php

namespace Tests\Unit\Services\Analytics;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Services\Analytics\SalesMetricsService;
use App\Traits\SaleCreation;
use Carbon\Carbon;
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

    /**
     * Setup necessary data for testing the sales metrics service.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SalesMetricsService();

        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        $this->product1 = Product::factory()->create([
            'category_id' => $category1->id,
            'sale' => 2000,
        ]);
        $this->product2 = Product::factory()->create([
            'category_id' => $category2->id,
            'sale' => 5000,
        ]);

        $this->startDate = now()->subDays(10)->toDateString();
        $this->endDate = now()->toDateString();
    }

    /**
     * Helper function to retrieve sales data for the test products between for the specified date range.
     *
     * @return Collection
     */
    private function getSales(): Collection
    {
        $productIds = [$this->product1->id, $this->product2->id];

        return Sale::whereBetween('sales.date', [$this->startDate, $this->endDate])
            ->with('products')
            ->whereHas('products', function ($q) use ($productIds) {
                $q->whereIn('products.id', $productIds);
            })
            ->get();
    }

    /**
     * Helper function to retrieve sales data grouped by date for the test products for the specified date range.
     *
     * @return Collection
     */
    protected function getSalesGroupedByDate(): Collection
    {
        $productIds = [$this->product1->id, $this->product2->id];

        return Sale::whereBetween('date', [$this->startDate, $this->endDate])
            ->whereHas('products', function ($query) use ($productIds) {
                $query->whereIn('product_id', $productIds);
            })
            ->with('products.category')
            ->get()
            ->groupBy(function ($sale) {
                return Carbon::parse($sale->date)->format('Y-m-d');
            });
    }

    /**
     * Test the `countSales` method to ensure it correctly counts the total number of sales.
     */
    public function testCountSales(): void
    {
        $sales = $this->getSales();
        $this->assertEquals(0, $this->service->countSales($sales));

        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(1, $this->service->countSales($sales));

        $this->createSale(collect([$this->product2]), [3], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(2, $this->service->countSales($sales));
    }

    /**
     * Test the `getHighestSale` method to ensure it returns the highest sale value.
     */
    public function testGetHighestSale(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $sales = $this->getSales();
        $this->assertEquals(0, $this->service->getHighestSale($sales));

        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(100.00, $this->service->getHighestSale($sales));

        $this->createSale($products, [1, 1], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(100.00, $this->service->getHighestSale($sales));

        $this->createSale(collect([$this->product2]), [10], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(500.00, $this->service->getHighestSale($sales));

        $this->createSale($products, [10, 6], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(500.00, $this->service->getHighestSale($sales));
    }

    /**
     * Test the `getLowestSale` method to ensure it returns the lowest sale value.
     */
    public function testGetLowestSale(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $sales = $this->getSales();
        $this->assertEquals(0, $this->service->getLowestSale($sales));

        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(100.00, $this->service->getLowestSale($sales));

        $this->createSale(collect([$this->product2]), [5], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(100.00, $this->service->getLowestSale($sales));

        $this->createSale(collect([$this->product1]), [2], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(40.00, $this->service->getLowestSale($sales));

        $this->createSale(collect([$this->product2]), [1], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(40.00, $this->service->getLowestSale($sales));

        $this->createSale($products, [1, 1], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(40.00, $this->service->getLowestSale($sales));
    }

    /**
     * Test the `calculateTotalItemsSold` method to ensure it correctly calculates the total items sold.
     */
    public function testCalculateTotalItemsSold(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $sales = $this->getSales();
        $this->assertEquals(0, $this->service->calculateTotalItemsSold($sales));

        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(5, $this->service->calculateTotalItemsSold($sales));

        $this->createSale(collect([$this->product2]), [3], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(8, $this->service->calculateTotalItemsSold($sales));

        $this->createSale($products, [10, 1], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(19, $this->service->calculateTotalItemsSold($sales));
    }

    /**
     * Test the `calculateTotalSalesValue` method to ensure it correctly calculates the total sales value.
     */
    public function testCalculateTotalSalesValue(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $sales = $this->getSales();
        $this->assertEquals(0, $this->service->calculateTotalSalesValue($sales));

        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(100.00, $this->service->calculateTotalSalesValue($sales));

        $this->createSale(collect([$this->product2]), [4], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(300.00, $this->service->calculateTotalSalesValue($sales));

        $this->createSale($products, [10, 20], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(1500.00, $this->service->calculateTotalSalesValue($sales));
    }

    /**
     * Test the `getMaxItemsSoldInSale` method to ensure it returns the maximum items sold in a single sale.
     */
    public function testGetMaxItemsSoldInSale(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $sales = $this->getSales();
        $this->assertEquals(0, $this->service->getMaxItemsSoldInSale($sales));

        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(5, $this->service->getMaxItemsSoldInSale($sales));

        $this->createSale(collect([$this->product2]), [10], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(10, $this->service->getMaxItemsSoldInSale($sales));

        $this->createSale($products, [10, 5], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(15, $this->service->getMaxItemsSoldInSale($sales));

        $this->createSale($products, [1, 5], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(15, $this->service->getMaxItemsSoldInSale($sales));
    }

    /**
     * Test the `getMinItemsSoldInSale` method to ensure it returns the minimum items sold in a single sale.
     */
    public function testGetMinItemsSoldInSale(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $sales = $this->getSales();
        $this->assertEquals(0, $this->service->getMinItemsSoldInSale($sales));

        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(5, $this->service->getMinItemsSoldInSale($sales));

        $this->createSale(collect([$this->product1]), [2], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(2, $this->service->getMinItemsSoldInSale($sales));

        $this->createSale($products, [2, 1], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(2, $this->service->getMinItemsSoldInSale($sales));

        $this->createSale($products, [1, 1], $this->startDate, $this->endDate);
        $sales = $this->getSales();
        $this->assertEquals(2, $this->service->getMinItemsSoldInSale($sales));
    }

    /**
     * Test the `mapAllSales` method to ensure it maps sales data correctly by date.
     */
    public function testMapAllSales(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $this->createSale($products, [5, 3], $this->startDate, Carbon::parse($this->startDate)->addDays(1));
        $sales = $this->getSalesGroupedByDate();
        $allSales = $this->service->mapAllSales($sales);
        $this->assertCount(1, $allSales);
        $this->assertEquals(250.00, $allSales[0]['total_sale']);
        $this->assertEquals(8, $allSales[0]['items']);

        $this->createSale(collect([$this->product1]), [10], $this->endDate, Carbon::parse($this->endDate)->subDays(1)->toDateString());
        $sales = $this->getSalesGroupedByDate();
        $allSales = $this->service->mapAllSales($sales);
        $this->assertCount(2, $allSales);
        $this->assertEquals(250.00, $allSales[0]['total_sale']);
        $this->assertEquals(8, $allSales[0]['items']);
        $this->assertEquals(200.00, $allSales[1]['total_sale']);
        $this->assertEquals(10, $allSales[1]['items']);

        $this->createSale(collect([$this->product2]), [10], $this->endDate, Carbon::parse($this->endDate)->subDays(1)->toDateString());
        $sales = $this->getSalesGroupedByDate();
        $allSales = $this->service->mapAllSales($sales);
        $this->assertCount(2, $allSales);
        $this->assertEquals(250.00, $allSales[0]['total_sale']);
        $this->assertEquals(8, $allSales[0]['items']);
        $this->assertEquals(700.00, $allSales[1]['total_sale']);
        $this->assertEquals(20, $allSales[1]['items']);
    }


    /**
     * Test the `mapSalesPerProduct` method to ensure it maps sales data correctly per product.
     */
    public function testMapSalesPerProduct(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $this->createSale($products, [5, 3], $this->startDate, $this->endDate);
        $sales = $this->getSalesGroupedByDate();
        $salesPerProduct = $this->service->mapSalesPerProduct($sales);
        $this->assertCount(2, $salesPerProduct);
        $this->assertEquals($this->product1->id, $salesPerProduct[0]['product_id']);
        $this->assertEquals(100.00, $salesPerProduct[0]['total_sale']);
        $this->assertEquals(5, $salesPerProduct[0]['quantity']);
        $this->assertEquals($this->product2->id, $salesPerProduct[1]['product_id']);
        $this->assertEquals(150.00, $salesPerProduct[1]['total_sale']);
        $this->assertEquals(3, $salesPerProduct[1]['quantity']);

        $this->createSale(collect([$this->product1]), [7], $this->startDate, Carbon::parse($this->startDate)->addDays(1)->toDateString());
        $this->createSale(collect([$this->product2]), [2], $this->startDate, Carbon::parse($this->startDate)->addDays(1)->toDateString());
        $sales = $this->getSalesGroupedByDate();
        $salesPerProduct = $this->service->mapSalesPerProduct($sales);
        $this->assertCount(2, $salesPerProduct);
        $this->assertEquals($this->product1->id, $salesPerProduct[0]['product_id']);
        $this->assertEquals(240.00, $salesPerProduct[0]['total_sale']);
        $this->assertEquals(12, $salesPerProduct[0]['quantity']);
        $this->assertEquals($this->product2->id, $salesPerProduct[1]['product_id']);
        $this->assertEquals(250.00, $salesPerProduct[1]['total_sale']);
        $this->assertEquals(5, $salesPerProduct[1]['quantity']);
    }

    /**
     * Test the `mapSalesPerCategory` method to ensure it maps sales data correctly per category.
     */
    public function testMapSalesPerCategory(): void
    {
        $products = collect([$this->product1, $this->product2]);

        $this->createSale($products, [5, 3], $this->startDate, Carbon::parse($this->startDate)->addDays(1)->toDateString());
        $sales = $this->getSalesGroupedByDate();
        $salesPerCategory = $this->service->mapSalesPerCategory($sales);
        $this->assertCount(2, $salesPerCategory);
        $this->assertEquals($this->product1->category_id, $salesPerCategory[0]['category_id']);
        $this->assertEquals(100.00, $salesPerCategory[0]['total_sale']);
        $this->assertEquals(5, $salesPerCategory[0]['quantity']);
        $this->assertEquals($this->product2->category_id, $salesPerCategory[1]['category_id']);
        $this->assertEquals(150.00, $salesPerCategory[1]['total_sale']);
        $this->assertEquals(3, $salesPerCategory[1]['quantity']);

        $this->createSale(collect([$this->product1]), [5], $this->startDate, Carbon::parse($this->startDate)->addDays(1)->toDateString());
        $this->createSale(collect([$this->product2]), [3], $this->startDate, Carbon::parse($this->startDate)->addDays(1)->toDateString());
        $sales = $this->getSalesGroupedByDate();
        $salesPerCategory = $this->service->mapSalesPerCategory($sales);
        $this->assertCount(2, $salesPerCategory);
        $this->assertEquals($this->product1->category_id, $salesPerCategory[0]['category_id']);
        $this->assertEquals(200.00, $salesPerCategory[0]['total_sale']);
        $this->assertEquals(10, $salesPerCategory[0]['quantity']);
        $this->assertEquals($this->product2->category_id, $salesPerCategory[1]['category_id']);
        $this->assertEquals(300.00, $salesPerCategory[1]['total_sale']);
        $this->assertEquals(6, $salesPerCategory[1]['quantity']);

        $this->createSale(collect([$this->product1]), [1], $this->endDate, Carbon::parse($this->endDate)->subDays(1)->toDateString());
        $this->createSale(collect([$this->product2]), [1], $this->endDate, Carbon::parse($this->endDate)->subDays(1)->toDateString());
        $sales = $this->getSalesGroupedByDate();
        $salesPerCategory = $this->service->mapSalesPerCategory($sales);
        $this->assertCount(4, $salesPerCategory);
        $this->assertEquals($this->product1->category_id, $salesPerCategory[0]['category_id']);
        $this->assertEquals(200.00, $salesPerCategory[0]['total_sale']);
        $this->assertEquals(10, $salesPerCategory[0]['quantity']);
        $this->assertEquals($this->product1->category_id, $salesPerCategory[1]['category_id']);
        $this->assertEquals(20.00, $salesPerCategory[1]['total_sale']);
        $this->assertEquals(1, $salesPerCategory[1]['quantity']);
        $this->assertEquals($this->product2->category_id, $salesPerCategory[2]['category_id']);
        $this->assertEquals(300.00, $salesPerCategory[2]['total_sale']);
        $this->assertEquals(6, $salesPerCategory[2]['quantity']);
        $this->assertEquals($this->product2->category_id, $salesPerCategory[3]['category_id']);
        $this->assertEquals(50.00, $salesPerCategory[3]['total_sale']);
        $this->assertEquals(1, $salesPerCategory[3]['quantity']);
    }
}
