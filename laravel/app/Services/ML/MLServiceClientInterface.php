<?php

namespace App\Services\ML;

interface MLServiceClientInterface
{
    public function predictDemand(array $data): mixed;

    public function exportSalesData(array $data): mixed;
}
