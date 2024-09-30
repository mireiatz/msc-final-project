<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use App\Jobs\ExportSalesToFile as ExportSalesToFileJob;
use Illuminate\Support\Facades\Storage;

class ExportSalesToFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:export-sales-to-file {start_date?} {end_date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export sales data from DB into a CSV file in {file_path?} for {start_date?} - {end_date?}';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        $startDate = $this->argument('start_date');
        $endDate = $this->argument('end_date');

        // Build the filepath and name
        $filename = 'exported_sales_' . now()->timestamp;
        if($startDate) {
            $filename .= '_' . $startDate;
        }
        if($endDate) {
            $filename .= '_' . $endDate;
        }
        $filename .= '.csv';

        try {
            // Ensure directory exists using Storage facade
            Storage::disk('historical_data_raw')->makeDirectory('', 0755, true);
        } catch (Exception $e) {
            throw new Exception('ExportSalesToFile command failed to prepare directory to dispatch job. | Error: ' . $e->getMessage());
        }

        ExportSalesToFileJob::dispatch($filename, $startDate, $endDate);
    }
}
