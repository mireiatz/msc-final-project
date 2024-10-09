<?php
namespace Tests\Unit\Jobs;

use App\Jobs\RunDemandPredictionRequests;
use App\Models\Product;
use App\Services\ML\MLServiceClientInterface;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RunDemandPredictionRequestsTest extends TestCase
{
    use RefreshDatabase;

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
     * Test the `handle` method to ensure that the job requests predictions and stores them.
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     *
     * @throws Exception
     */
    public function testHandleGeneratesCsvAndStoresPredictions(): void
    {
        // Fake the queue and create products
        Queue::fake();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        // Create a mock of the MLServiceClientInterface
        $mlServiceClientMock = $this->createMock(MLServiceClientInterface::class);

        // Mock the predictDemand method to return fake predictions
        $mlServiceClientMock->method('predictDemand')->willReturn([
            'predictions' => json_encode([
                ['product_id' => $product1->id, 'date' => '2023-01-01', 'value' => 100],
                ['product_id' => $product2->id, 'date' => '2023-01-02', 'value' => 200],
            ]),
        ]);

        // Create the job instance
        $job = new RunDemandPredictionRequests(7, 30);

        // Call the handle method with the mock
        $job->handle($mlServiceClientMock);

        // Assert that the predictions were stored correctly
        $this->assertDatabaseHas('predictions', [
            'product_id' => $product1->id,
            'date' => '2023-01-01',
            'value' => 100,
        ]);

        $this->assertDatabaseHas('predictions', [
            'product_id' => $product2->id,
            'date' => '2023-01-02',
            'value' => 200,
        ]);
    }

    /**
     * Test the job handles exceptions correctly during prediction requests.
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testHandleCatchesExceptions(): void
    {
        // Fake the queue and create a product
        Queue::fake();
        $product = Product::factory()->create();

        // Create a mock of the MLServiceClientInterface
        $mlServiceClientMock = $this->createMock(MLServiceClientInterface::class);

        // Simulate an exception when calling predictDemand
        $mlServiceClientMock->method('predictDemand')->willThrowException(new Exception('Prediction request failed'));

        // Create the job instance
        $job = new RunDemandPredictionRequests(7, 30);

        // Call the handle method and catch the exception
        $this->expectException(Exception::class);
        $job->handle($mlServiceClientMock);
    }
}
