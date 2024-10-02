<?php

namespace App\Services\ML;

interface MLServiceClientInterface
{
    public function makeRequest(string $method, string $endpoint, ?array $data = null): mixed;

    public function predictDemand(array $data): mixed;

    public function exportSalesData(array $data): mixed;
}
