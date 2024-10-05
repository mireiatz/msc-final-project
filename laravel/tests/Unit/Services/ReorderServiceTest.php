<?php

namespace Tests\Unit\Services\Analytics;
namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Prediction;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Store;
use App\Services\PrescriptiveAnalytics\ReorderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReorderServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReorderService $service;
    private Provider $provider;
    private Category $category;
    private Product $product1;
    private Product $product2;

    /**
     * Setup necessary data for testing the reorder suggestions service.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Instantiate the service
        $this->service = new ReorderService();

        // Create elements needed throughout
        $this->store = Store::factory()->create();

        $this->provider1 = Provider::factory()->create([
            'lead_days' => 5,
        ]);

        $this->provider2 = Provider::factory()->create([
            'lead_days' => 1,
        ]);

        $this->category = Category::factory()->create(['name' => 'Category 1']);

        $this->product1 = Product::factory()->create([
            'provider_id' => $this->provider1->id,
            'category_id' => $this->category->id,
            'name' => 'Product 1',
            'cost' => 1000,
            'unit' => 'kg',
            'amount_per_unit' => 10,
        ]);

        $this->product2 = Product::factory()->create([
            'provider_id' => $this->provider2->id,
            'category_id' => $this->category->id,
            'name' => 'Product 2',
            'cost' => 1000,
            'unit' => 'kg',
            'amount_per_unit' => 10,
        ]);
    }

    /**
     * Test the `getReorderSuggestions` method to ensure correct reorder data structure and values.
     */
    public function testGetReorderSuggestions(): void
    {
        // Simulate stock balance
        InventoryTransaction::create([
            'parent_type' => 'App\Models\SaleProduct',
            'parent_id' => 'id',
            'store_id' => $this->store->id,
            'product_id' => $this->product1->id,
            'date' => now()->subDays(2),
            'quantity' => 10,
            'stock_balance' => 10,
        ]);

        // Simulate predictions for the next 7 days
        for ($i = 0; $i < 7; $i++) {
            Prediction::factory()->create([
                'product_id' => $this->product1->id,
                'date' => now()->addDays($i),
                'value' => 10,  // Predicted daily demand
            ]);
        }

        // Run service function
        $reorderSuggestions = $this->service->getReorderSuggestions($this->provider1, $this->category);

        // Assert reorder characteristics
        $this->assertCount(1, $reorderSuggestions);
        $suggestion = $reorderSuggestions[0];
        $this->assertEquals($this->product1->id, $suggestion['product_id']);
        $this->assertEquals('Product 1', $suggestion['product_name']);
        $this->assertEquals(10, $suggestion['stock_balance']);
        $this->assertEquals(20, $suggestion['predicted_demand']); // 5 lead days, therefore, 2 days of predictions
        $this->assertEquals(10, $suggestion['reorder_amount']); // Buffer = 0 and stock balance = 10
    }

    /**
     * Test the `getGetProducts` method to further ensure correct reorder data, specifically products retrieved.
     */
    public function testGetProducts(): void
    {
        // Simulate stock balance
        InventoryTransaction::create([
            'parent_type' => 'App\Models\SaleProduct',
            'parent_id' => 'id',
            'store_id' => $this->store->id,
            'product_id' => $this->product2->id,
            'date' => now()->subDays(2),
            'quantity' => 20,
            'stock_balance' => 10,
        ]);

        for ($i = 0; $i < 7; $i++) {
            Prediction::factory()->create([
                'product_id' => $this->product2->id,
                'date' => now()->addDays($i),
                'value' => 5 // Predicted daily demand
            ]);
        }

        // Run the service function
        $products = $this->service->getProducts($this->provider2->id, $this->category->id, now()->addDays(1), now()->addDays(12)); // From lead days

        // Assert the products and info retrieved
        $this->assertCount(1, $products);
        $product = $products[0];
        $this->assertEquals($this->product2->id, $product->id);
        $this->assertEquals(20, $product->stock_balance);
        $this->assertEquals(30, $product->total_predicted_demand);
    }

    /**
     * Test the `calculateSafetyStock` method to ensure accurate buffer stock calculations.
     */
    public function testCalculateSafetyStock(): void
    {
        // Run service function with a variety of cases
        $safetyStock = $this->service->calculateSafetyStock(20, 10, 5);
        $this->assertEquals(50, $safetyStock); // Standard calculation

        $safetyStock = $this->service->calculateSafetyStock(10, 10, 5);
        $this->assertEquals(0, $safetyStock); // No buffer required when max == avg

        $safetyStock = $this->service->calculateSafetyStock(0, 10, 5);
        $this->assertEquals(0, $safetyStock); // Now returns 0 instead of negative

        $safetyStock = $this->service->calculateSafetyStock(20, 0, 5);
        $this->assertEquals(100, $safetyStock); // Safety stock based on max only

        $safetyStock = $this->service->calculateSafetyStock(20, 10, 0);
        $this->assertEquals(0, $safetyStock); // No buffer required if no lead time

        $safetyStock = $this->service->calculateSafetyStock(10000, 9000, 30);
        $this->assertEquals(30000, $safetyStock); // Large buffer for high lead times and demands
    }

    /**
     * Test the `calculateReorderSuggestion` method to ensure accurate reorder suggestion values.
     */
    public function testCalculateReorderSuggestion(): void
    {
        // Run service function with a variety of cases
        $reorderAmount = $this->service->calculateReorderSuggestion(70, 50, 20);
        $this->assertEquals(100, $reorderAmount); // Standard calculation

        $reorderAmount = $this->service->calculateReorderSuggestion(0, 50, 20);
        $this->assertEquals(30, $reorderAmount);  // Only reorder to meet safety stock

        $reorderAmount = $this->service->calculateReorderSuggestion(70, 50, 150);
        $this->assertEquals(0, $reorderAmount);  // Stock is more than enough

        $reorderAmount = $this->service->calculateReorderSuggestion(70, 50, -10);
        $this->assertEquals(130, $reorderAmount);  // Negative stock balance for precaution

        $reorderAmount = $this->service->calculateReorderSuggestion(0, 0, 0);
        $this->assertEquals(0, $reorderAmount);  // Nothing to reorder

        $reorderAmount = $this->service->calculateReorderSuggestion(100000, 50000, 20000);
        $this->assertEquals(130000, $reorderAmount);  // Large reorder to meet demand and buffer
    }
}
