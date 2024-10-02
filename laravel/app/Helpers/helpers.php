<?php

use Carbon\Carbon;
use League\Csv\Reader;
use League\Csv\Exception;

if (!function_exists('parseCsvFile')) {

    /**
     * Parse a CSV file into an array of rows.
     *
     * @param string $file
     * @return array
     * @throws Exception
     */
    function parseCsvFile(string $file): array
    {
        $csv = Reader::createFromPath($file, 'r');
        $csv->setHeaderOffset(0);  // Assumes the first row is the header

        return iterator_to_array($csv->getRecords()); // Convert the CSV to an array
    }
}
if (!function_exists('generateDateRange')) {

    /**
     * Generate a date range between two dates.
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    function generateDateRange(?string $startDate = null, ?string $endDate = null): array
    {
        $start = Carbon::parse($startDate ?? Carbon::today());
        $end = Carbon::parse($endDate ?? Carbon::today());
        $dates = [];

        while ($start->lte($end)) {
            $dates[] = $start->format('Y-m-d');
            $start->addDay();
        }

        return $dates;
    }

}
