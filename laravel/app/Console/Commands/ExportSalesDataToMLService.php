<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use App\Jobs\ExportDailySalesDataToMLService as ExportDailySalesDataToMLServiceJob;
use Illuminate\Support\Facades\Log;

class ExportSalesDataToMLService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ml:export-sales-data-to-ml-service {startDate?} {endDate?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export historical sales data to the ML microservice {startDate?} - {endDate?}';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        // Grab arguments
        $startDate = $this->argument('startDate');
        $endDate = $this->argument('endDate');

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
            ExportDailySalesDataToMLServiceJob::dispatch($startDate, $endDate);
            $this->info("ExportSalesDataToMLServiceJob dispatched successfully");
        } catch (Exception $e) {
            Log::error('Command: ExportSalesDataToMLService | Failed to dispatch ExportSalesDataToMLService job | Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate the date format.
     *
     * @param string $date
     * @return bool
     */
    private function isValidDate(string $date): bool
    {
        return Carbon::hasFormat($date, 'Y-m-d') && Carbon::createFromFormat('Y-m-d', $date)->format('Y-m-d') === $date;
    }
}
