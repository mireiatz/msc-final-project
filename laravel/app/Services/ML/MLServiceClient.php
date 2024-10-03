<?php

namespace App\Services\ML;

use Exception;
use Illuminate\Http\Client\PendingRequest;
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
     * Authorise the HTTP request by adding the Bearer Token.
     *
     * @return PendingRequest
     */
    private function authorise(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);
    }

    /**
     * Send a request to the ML service.
     *
     * @param string $method
     * @param string $endpoint
     * @param array|null $data
     * @param $http
     * @return mixed
     * @throws Exception
     */
    public function sendRequest(string $method, string $endpoint, ?array $data, $http): mixed
    {
        try {
            // Execute the request
            $response =  $http->$method($this->baseUri . $endpoint, $data);

            return $response->json();

        } catch (Exception $e) {
            Log::error('MLServiceClient failed to make a request (' . $method . ') to ' . $endpoint . ' | Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send a JSON-based request.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    private function makeJsonRequest(string $method, string $endpoint, array $data): mixed
    {
        $http = $this->authorise()->asJson();
        return $this->sendRequest($method, $endpoint, $data, $http);
    }

    /**
     * Send a multipart file-based request.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    private function makeFileRequest(string $method, string $endpoint, array $data): mixed
    {
        $http = $this->authorise()
            ->attach(
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
        return $this->sendRequest($method, $endpoint, $data, $http);
    }

    /**
     * Request demand prediction.
     *
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    public function predictDemand(array $data): mixed
    {
        return $this->makeJsonRequest('post', '/predict-demand', $data);
    }

    /**
     * Export data through a file.
     *
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    public function exportSalesData(array $data): mixed
    {
        return $this->makeFileRequest('post', '/export-sales-data', $data);
    }
}
