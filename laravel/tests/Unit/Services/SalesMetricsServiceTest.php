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
}
