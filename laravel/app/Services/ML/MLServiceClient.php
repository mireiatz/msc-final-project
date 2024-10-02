<?php

namespace App\Services\ML;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MLServiceClient implements MLServiceClientInterface
{
    protected string $apiKey;
    protected string $baseUri;

    public function __construct()
    {
        $this->apiKey = config('services.ml.api_key');
        $this->baseUri = config('services.ml.base_uri');
    }

    /**
     * Make a request to the ML service.
     *
     * @param string $method
     * @param string $endpoint
     * @param array|null $data
     * @return mixed
     * @throws Exception
     */
    public function makeRequest(string $method, string $endpoint, ?array $data = null): mixed
    {
        try {
            // Prepare request depending on the payload
            $preparedRequest = $this->prepareRequestWithPayload($data);

            // Execute the request
            $response = $preparedRequest->$method($this->baseUri . $endpoint);

            // Check if the response was successful
            if ($response->successful()) {
                return $response->json();
            } else {
                throw new Exception('MLServiceClient request to ' . $endpoint . ' failed | Status: ' . $response->status() . ' | Response: ' . substr($response->body(), 0, 500));
            }
        } catch (Exception $e) {
            Log::error('MLServiceClient failed to make a request (' . $method . ') to ' . $endpoint . ' | Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Helper function to prepare the request for file upload or regular JSON.
     *
     * @param array $data
     * @return mixed
     */
    private function prepareRequestWithPayload(array $data): mixed
    {
        $http = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);

        if (isset($data['file']) && isset($data['content'])) {
            return $http->attach(
                'file',
                $data['content'],
                $data['file']
            )->withOptions([
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => $data['content'],
                        'filename' => $data['file'],
                    ],
                    [
                        'name'     => 'metadata',
                        'contents' => json_encode($data['metadata']),
                    ]
                ]
            ]);
        }

        // Regular JSON request
        return $http->asJson();

    }

    /**
     * Function to request demand prediction.
     *
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    public function predictDemand(array $data): mixed
    {
        return $this->makeRequest('post', '/predict-demand', $data);
    }

    /**
     * Function to export data to the ML service.
     *
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    public function exportSalesData(array $data): mixed
    {
        return $this->makeRequest('post', '/export-sales-data', $data);
    }
}
