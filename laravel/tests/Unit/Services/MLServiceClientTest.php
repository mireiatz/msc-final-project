<?php

namespace Tests\Unit\Services;

use App\Services\ML\MLServiceClient;
use Exception;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MLServiceClientTest extends TestCase
{
    protected MLServiceClient $mlServiceClient;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock config values
        Config::set('services.ml.api_key', 'test-api-key');
        Config::set('services.ml.base_uri', 'https://ml-service.com');

        // Instantiate the client
        $this->mlServiceClient = new MLServiceClient();    }

    /**
     * Test `predictDemand` method makes a POST request and returns a valid response.
     * @throws Exception
     */
    public function testPredictDemandMakesPostRequest(): void
    {
        // Mock an API response
        Http::fake([
            'ml-service.com/predict-demand' => Http::response(['result' => 'success'], 200),
        ]);

        // Run the function in the service
        $response = $this->mlServiceClient->predictDemand(['input_data' => 'test']);

        // Assert the HTTP POST request
        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://ml-service.com/predict-demand'
                && $request->hasHeader('Authorization', 'Bearer ' . config('services.ml.api_key'))
                && $request->data() === ['input_data' => 'test'];
        });

        // Assert that the response is correct
        $this->assertEquals(['result' => 'success'], $response);
    }

    /**
     * Test `predictDemand` method throws an exception on failure.
     */
    public function testPredictDemandThrowsExceptionOnFailure(): void
    {
        // Mock an API error response
        Http::fake([
            'ml-service.com/predict-demand' => Http::response(null, 500),
        ]);

        // Mock logging
        Log::shouldReceive('error')->once();

        $this->expectException(Exception::class);

        // Run the function in the service
        $this->mlServiceClient->predictDemand(['input_data' => 'test']);
    }

    /**
     * Test `exportSalesData` method makes a POST request with a file and returns a valid response.
     * @throws Exception
     */
    public function testExportSalesDataMakesPostRequest(): void
    {
        // Mock an API response
        Http::fake([
            'https://ml-service.com/export-sales-data' => Http::response(['result' => 'file_exported'], 200),
        ]);

        // Prepare the file data for the request
        $fileData = [
            'file' => 'sales_data.csv',
            'content' => 'CSV content here',
            'metadata' => [
                'name' => 'value',
                'contents' => 'value'
            ],
        ];

        // Run the function in the service
        $response = $this->mlServiceClient->exportSalesData($fileData);

        // Assert the HTTP POST request
        Http::assertSent(function ($request) use ($fileData) {
            return $request->method() === 'POST'
                && $request->url() === 'https://ml-service.com/export-sales-data'
                && $request->hasFile('file')  // Verify file attached
                && $request->hasHeader('Authorization', 'Bearer test-api-key');  // Verify authorisation header
        });

        // Assert that the response is correct
        $this->assertEquals(['result' => 'file_exported'], $response);
    }

    /**
     * Test `exportSalesData` throws an exception on failure.
     */
    public function testExportSalesDataThrowsExceptionOnFailure(): void
    {
        // Mock an API error response
        Http::fake([
            'ml-service.com/export-sales-data' => Http::response(null, 500),
        ]);

        // Mock logging
        Log::shouldReceive('error')->once();

        $this->expectException(Exception::class);

        // Run the function in the service
        $this->mlServiceClient->exportSalesData([
            'file' => 'sales_data.csv',
            'content' => 'CSV content here',
            'metadata' => ['key' => 'value'],
        ]);
    }
}
