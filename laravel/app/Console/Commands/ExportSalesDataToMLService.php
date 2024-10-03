<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use App\Jobs\ExportSalesDataToMLService as ExportSalesDataToMLServiceJob;
use Illuminate\Support\Facades\Log;

class ExportSalesDataToMLService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ml:export-sales-data-to-ml-service {dataType} {startDate?} {endDate?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export {dataType} sales data to the ML microservice {startDate?} - {endDate?}';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        // Grab arguments
        $dataType = $this->argument('dataType');
        $startDate = $this->argument('startDate');
        $endDate = $this->argument('endDate');

        // Validate data type format and prepare the parameters
        $details = explode('_', $dataType);
        if (count($details) !== 2 || !in_array($details[0], ['historical']) || !in_array($details[1], ['weekly', 'daily'])) {
            $this->error('Invalid dataType format, use historical_weekly or historical_daily.');
            Log::error('Command: ExportSalesDataToMLService | Invalid dataType format: ' . $dataType);
            return;
        }
        $type = $details[0];
        $format = $details[1];

        // Validate date format
        if ($startDate && !$this->isValidDate($startDate)) {
            $this->error('Invalid startDate format, use YYYY-MM-DD');
            Log::error('Command: ExportSalesDataToMLService | Invalid startDate format: ' . $startDate);
            return;
        }

        if ($endDate && !$this->isValidDate($endDate)) {
            $this->error('Invalid endDate format, use YYYY-MM-DD');
            Log::error('Command: ExportSalesDataToMLService | Invalid endDate format: ' . $endDate);
            return;
        }

        //Dispatch the job
        try {
            ExportSalesDataToMLServiceJob::dispatch($type, $format, $startDate, $endDate);
            $this->info("ExportSalesDataToMLServiceJob dispatched successfully");
        } catch (Exception $e) {
            Log::error('Command: ExportSalesDataToMLService | Failed to dispatch ExportSalesDataToMLService job | Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate the date format.
     * @param string $date
     * @return bool
     */
    private function isValidDate(string $date): bool
    {
        return Carbon::hasFormat($date, 'Y-m-d') && Carbon::createFromFormat('Y-m-d', $date)->format('Y-m-d') === $date;
    }
}
