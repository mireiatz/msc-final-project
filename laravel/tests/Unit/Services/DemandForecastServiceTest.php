<?php

namespace Tests\Unit\Services\Analytics;
namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\Prediction;
use App\Models\Product;
use App\Services\PredictiveAnalytics\DemandForecastService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemandForecastServiceTest extends TestCase
{
    use RefreshDatabase;

    private DemandForecastService $service;
    private Category $category1;
    private Category $category2;
    private Product $product1;
    private Product $product2;

    /**
     * Setup necessary data for testing the demand forecast service.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Instantiate the service
        $this->service = new DemandForecastService();

        // Create elements needed throughout
        $this->category1 = Category::factory()->create(['name' => 'Category 1']);
        $this->category2 = Category::factory()->create(['name' => 'Category 2']);

        $this->product1 = Product::factory()->create([
            'category_id' => $this->category1->id,
            'name' => 'Product 1',
        ]);
        $this->product2 = Product::factory()->create([
            'category_id' => $this->category2->id,
            'name' => 'Product 2',
        ]);
    }

    /**
     * Test the `getCategoryLevelDemandForecast` method to ensure correct demand forecast at category level.
     */
    public function testGetCategoryLevelDemandForecast(): void
    {
        // Create predictions
        Prediction::factory()->create([
            'product_id' => $this->product1->id,
            'date' => Carbon::today(),
            'value' => 100,
        ]);

        Prediction::factory()->create([
            'product_id' => $this->product2->id,
            'date' => Carbon::today(),
            'value' => 200,
        ]);

        // Run service function
        $forecast = $this->service->getCategoryLevelDemandForecast();

        // Assert forecast characteristics
        $this->assertCount(2, $forecast);
        $category1Forecast = collect($forecast)->firstWhere('id', $this->category1->id);
        $this->assertEquals('Category 1', $category1Forecast['name']);
        $this->assertEquals(100, $category1Forecast['predictions'][0]['value']);
        $category2Forecast = collect($forecast)->firstWhere('id', $this->category2->id);
        $this->assertEquals('Category 2', $category2Forecast['name']);
        $this->assertEquals(200, $category2Forecast['predictions'][0]['value']);
    }

    /**
     * Test the `getProductLevelDemandForecast` method to ensure correct demand forecast at product level, for a given category.
     */
    public function testGetProductLevelDemandForecast(): void
    {
        // Create predictions
        Prediction::factory()->create([
            'product_id' => $this->product1->id,
            'date' => Carbon::today(),
            'value' => 150,
        ]);

        Prediction::factory()->create([
            'product_id' => $this->product1->id,
            'date' => Carbon::tomorrow(),
            'value' => 250,
        ]);

        // Run the service function
        $forecast = $this->service->getProductLevelDemandForecast($this->category1);

        // Assert forecast characteristics
        $this->assertEquals('Category 1', $forecast['category']);
        $this->assertCount(1, $forecast['products']);
        $productForecast = $forecast['products'][0];
        $this->assertEquals($this->product1->id, $productForecast['id']);
        $this->assertEquals(150, $productForecast['predictions'][0]['value']);
        $this->assertEquals(250, $productForecast['predictions'][1]['value']);
    }

    /**
     * Test the `getWeeklyAggregatedDemandForecast` method to ensure predictions are aggregated per week in a 4-week forecast correctly.
     */
    public function testGetWeeklyAggregatedDemandForecast(): void
    {
        // Create weekly predictions
        $nextMonday = Carbon::now()->next('Monday');
        for ($i = 0; $i < 4; $i++) {
            $predictionDate = $nextMonday->copy()->addDays($i * 7);  // Every week on Monday
            Prediction::factory()->create([
                'product_id' => $this->product1->id,
                'date' => $predictionDate,
                'value' => 100 * ($i + 1), // Increasing demand for each week
            ]);
        }

        // Run the service function
        $forecast = $this->service->getWeeklyAggregatedDemandForecast($this->category1);

        // Assert the forecast characteristics
        $this->assertEquals($this->category1->id, $forecast['id']);
        $this->assertEquals($this->category1->name, $forecast['name']);
        $this->assertCount(4, $forecast['weeks']);  // 4 weeks of forecasts

        // Check the values for each week
        for ($i = 0; $i < 4; $i++) {
            $weekForecast = $forecast['weeks'][$i];
            $expectedWeekStart = $nextMonday->copy()->addWeeks($i)->format('d-m-Y');
            $this->assertEquals('Week of ' . $expectedWeekStart, $weekForecast['name']);
            $this->assertEquals(100 * ($i + 1), $weekForecast['value']);
        }
    }

    /**
     * Test the `getMonthAggregatedDemandForecast` method to ensure predictions are aggregated for 30 days per category.
     */
    public function testGetMonthAggregatedDemandForecast(): void
    {
        // Create predictions over the next 30 days
        for ($i = 0; $i < 30; $i++) {
            Prediction::factory()->create([
                'product_id' => $this->product1->id,
                'date' => Carbon::today()->addDays($i),
                'value' => 50,  // Constant value of 50 per day
            ]);

            Prediction::factory()->create([
                'product_id' => $this->product2->id,
                'date' => Carbon::today()->addDays($i),
                'value' => 30,  // Constant value of 30 per day
            ]);
        }

        // Run the service function
        $forecast = $this->service->getMonthAggregatedDemandForecast();

        // Assert forecast characteristics
        $this->assertCount(2, $forecast);
        $category1Forecast = collect($forecast)->firstWhere('id', $this->category1->id);
        $this->assertEquals('Category 1', $category1Forecast['name']);
        $this->assertEquals(1500, $category1Forecast['value']);
        $category2Forecast = collect($forecast)->firstWhere('id', $this->category2->id);
        $this->assertEquals('Category 2', $category2Forecast['name']);
        $this->assertEquals(900, $category2Forecast['value']);
    }
}
