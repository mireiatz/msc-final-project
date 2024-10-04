<?php
namespace Tests\Unit\Jobs;

use App\Jobs\RunDemandPredictionRequests;
use App\Models\Product;
use App\Traits\SaleCreation;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\RequestAndStoreDemandPredictions;
use Tests\TestCase;

class RunDemandPredictionRequestsTest extends TestCase
{
    use RefreshDatabase, SaleCreation;

    /**
     * Test that the job is dispatched.
     */
    public function testJobIsDispatched(): void
    {
        // Fake the queue
        Queue::fake();

        // Dispatch the job
        RunDemandPredictionRequests::dispatch();

        // Assert the job was pushed onto the queue and has correct data
        Queue::assertPushed(RunDemandPredictionRequests::class);
    }

    /**
     * Test the `collectPredictionData` method to ensure the correct data is fetched for predictions to be made.
     */
    public function testCollectPredictionData(): void
    {
        // Create elements needed
        $product = Product::factory()->create();
        $this->createSale(collect([$product]), [1], now()->subDays(5), now());
        Carbon::setTestNow(Carbon::today());

        // Create the job instance
        $job = new RunDemandPredictionRequests(7, 30);

        // Collect the prediction data
        $payload = $job->collectPredictionData();

        // Assert data in payload
        $this->assertCount(7, $payload['prediction_dates']);
        $this->assertNotEmpty($payload['products']);
        $this->assertEquals($product->id, $payload['products'][0]['details']['source_product_id']);
        $this->assertArrayHasKey('historical_sales', $payload['products'][0]);
    }

    /**
     *  Test the `collectPredictionData` method to ensure the correct data is fetched for predictions to be made.
     *
     * @throws Exception
     */
    public function testJobChunksAndDispatchesCorrectData(): void
    {
        // Fake the queue and create elements needed
        Queue::fake();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $this->createSale(collect([$product1]), [1], now()->subMonths(2), now()->subDays(1));
        $this->createSale(collect([$product2]), [1], now()->subMonths(2), now()->subDays(1));

        // Create the job instance and run it
        $job = new class(7, 30) extends RunDemandPredictionRequests {
            public int $chunkSize = 1;  // Override chunk size
        };
        $job->handle();

        // Assert that a job was dispatched for each product
        Queue::assertPushed(RequestAndStoreDemandPredictions::class, 2);
    }
}
