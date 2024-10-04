<?php
namespace Tests\Unit\Jobs;

use App\Models\Product;
use Exception;
use PHPUnit\Framework\MockObject\Exception as PhpUnitException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\RequestAndStoreDemandPredictions;
use App\Services\ML\MLServiceClientInterface;
use ReflectionClass;
use Tests\TestCase;

class RequestAndStoreDemandPredictionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the job is dispatched with the correct data.
     */
    public function testJobIsDispatched(): void
    {
        // Fake the queue
        Queue::fake();

        // Sample data to pass into the job
        $data = ['input_data' => 'test'];

        // Dispatch the job
        RequestAndStoreDemandPredictions::dispatch($data);

        // Assert the job was pushed onto the queue and has correct data
        Queue::assertPushed(RequestAndStoreDemandPredictions::class, function ($job) use ($data) {
            $reflection = new ReflectionClass($job);
            $property = $reflection->getProperty('data');
            $jobData = $property->getValue($job);

            return $jobData === $data;
        });
    }

    /**
     * Test that the job calls the ML service and upserts predictions correctly.
     *
     * @throws PhpUnitException
     * @throws Exception
     */
    public function testJobCallsMlServiceAndStoresPredictions(): void
    {
        $product = Product::factory()->create();

        // Mock the MLServiceClient
        $mlServiceClient = $this->createMock(MLServiceClientInterface::class);
        $mlServiceClient->expects($this->once())
            ->method('predictDemand')
            ->willReturn(['predictions' => json_encode([
                [
                    'product_id' => $product->id,
                    'date' => '2024-10-10',
                    'value' => 50
                ],
                [
                    'product_id' => $product->id,
                    'date' => '2024-10-10',
                    'value' => 100 // Overwrites previous value
                ]
            ])
        ]);

        // Call the job and assert the value
        $job = new RequestAndStoreDemandPredictions(['input_data' => 'test']);
        $job->handle($mlServiceClient);
        $this->assertDatabaseHas('predictions', [
            'product_id' => $product->id,
            'date' => '2024-10-10',
            'value' => 100,
        ]);
    }
}
