<?php

namespace App\Traits;

use Carbon\Carbon;

trait RandomDate
{
    /**
     * Get a random date
     *
     * @return mixed
     */
    protected function getRandomDate(): string
    {
        $startDate = Carbon::create(2024);
        $endDate = Carbon::now();
        $randomTimestamp = rand($startDate->timestamp, $endDate->timestamp);
        $randomDate = Carbon::createFromTimestamp($randomTimestamp);

        return $randomDate->format('Y-m-d H:i:s');
    }
}
