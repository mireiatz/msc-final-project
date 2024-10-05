<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Services\DescriptiveAnalytics\SalesMetricsService;
use App\Traits\SaleCreation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SalesMetricsServiceTest extends TestCase
{
    use RefreshDatabase, SaleCreation;

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

        // Instantiate the service
        $this->service = new SalesMetricsService();

        // Create elements needed throughout
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
     * Test the `getOverviewMetrics` method to ensure overall data structure and correctness of the overview metrics.
     */
    public function testGetOverviewMetrics(): void
    {
        // Create sales within the specified date range
        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->endDate);
        $this->createSale(collect([$this->product2]), [3], $this->startDate, $this->endDate);

        // Run function in service
        $metrics = $this->service->getOverviewMetrics($this->startDate, $this->endDate);

        // Assert metrics
        $this->assertEquals(2, $metrics['sales_count']);
        $this->assertEquals(150, $metrics['highest_sale']);
        $this->assertEquals(100, $metrics['lowest_sale']);
        $this->assertEquals(8, $metrics['total_items_sold']);
        $this->assertEquals(250, $metrics['total_sales_value']);
        $this->assertEquals(5, $metrics['max_items_sold_in_sale']);
        $this->assertEquals(3, $metrics['min_items_sold_in_sale']);
    }

    /**
     * Test the `getDetailedMetrics` method to ensure overall data structure and correctness of the detailed metrics.
     */
    public function testGetDetailedMetrics(): void
    {
        // Create sales within the specified date range
        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->endDate);
        $this->createSale(collect([$this->product2]), [3], $this->startDate, $this->endDate);

        // Run function in service
        $detailedMetrics = $this->service->getDetailedMetrics($this->startDate, $this->endDate);

        // Assertions for 'all_sales'
        $this->assertCount(1, $detailedMetrics['all_sales']);
        $this->assertEquals(8, $detailedMetrics['all_sales'][0]['items']);
        $this->assertEquals(250, $detailedMetrics['all_sales'][0]['total_sale']);

        // Assertions for 'sales_per_category'
        $this->assertCount(2, $detailedMetrics['sales_per_category']);

        // Assert the details for each category
        foreach ($detailedMetrics['sales_per_category'] as $categorySales) {
            if ($categorySales['category_id'] === $this->product1->category_id) {
                $this->assertEquals(5, $categorySales['quantity']);
                $this->assertEquals(100, $categorySales['total_sale']);
            } else if ($categorySales['category_id'] === $this->product2->category_id) {
                $this->assertEquals(3, $categorySales['quantity']);
                $this->assertEquals(150, $categorySales['total_sale']);
            }
        }
    }

    /**
     * Test the `getSales` method to ensure the correct sales are retrieved.
     */
    public function testGetSales(): void
    {
        // Create sales in the date range
        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->endDate);
        $this->createSale(collect([$this->product2]), [3], $this->startDate, $this->endDate);

        // Run the service function
        $sales = $this->service->getSales($this->startDate, $this->endDate);

        // Assert values
        $this->assertCount(2, $sales);
        $this->assertEquals(10000, $sales[0]->sale); // In cents in the DB
        $this->assertEquals(15000, $sales[1]->sale);
    }

    /**
     * Test the `getHighestAndLowestSale` method to ensure the correct sales are selected.
     */
    public function testGetHighestAndLowestSale(): void
    {
        // Create sales
        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->endDate);
        $this->createSale(collect([$this->product2]), [3], $this->startDate, $this->endDate);

        // Run service function
        $sales = $this->service->getSales($this->startDate, $this->endDate);
        $highestSale = $this->service->getHighestSale($sales);
        $lowestSale = $this->service->getLowestSale($sales);

        // Assert values
        $this->assertEquals(150, $highestSale);
        $this->assertEquals(100, $lowestSale);
    }

    /**
     * Test the `getMAxAndMinItemsSoldInSale` method to ensure the correct sales are retrieved.
     */
    public function testGetMaxAndMinItemsSoldInSale(): void
    {
        // Create sales
        $this->createSale(collect([$this->product1]), [5], $this->startDate, $this->endDate);
        $this->createSale(collect([$this->product2]), [3], $this->startDate, $this->endDate);

        // Run service function
        $sales = $this->service->getSales($this->startDate, $this->endDate);
        $maxItemsSold = $this->service->getMaxItemsSoldInSale($sales);
        $minItemsSold = $this->service->getMinItemsSoldInSale($sales);

        // Asser values
        $this->assertEquals(5, $maxItemsSold);
        $this->assertEquals(3, $minItemsSold);
    }
}
