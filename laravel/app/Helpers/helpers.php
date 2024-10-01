<?php

use League\Csv\Reader;
use League\Csv\Exception;

if (!function_exists('parseCsvFile')) {

    /**
     * Parse the CSV file into an array of rows.
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
