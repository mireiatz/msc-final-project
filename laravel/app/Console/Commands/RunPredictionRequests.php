<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use App\Jobs\RunPredictionRequests as RunPredictionRequestsJob;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class RunPredictionRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:run-prediction-requests {daysToPredict?} {historicalDays?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process requests for predictions based on amount of days to predict and how many sales dates details are needed for historical data preparation';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        // Grab arguments
        $daysToPredict = $this->argument('daysToPredict') !== null ? (int) $this->argument('daysToPredict') : null;
        $historicalDays = $this->argument('historicalDays') !== null ? (int) $this->argument('historicalDays') : null;

        // Validate the provided arguments only if they're entered
        if (($daysToPredict !== null && $daysToPredict <= 0) || ($historicalDays !== null && $historicalDays <= 0)) {
            Log::error('Invalid number of days for prediction or historical data');
            throw new InvalidArgumentException('If provided, daysToPredict and historicalDays must be positive integers');
        }

        //Dispatch the job
        try {
            RunPredictionRequestsJob::dispatch($daysToPredict, $historicalDays);
            $this->info("RunPredictionRequestsJob dispatched successfully");
        } catch (Exception $e) {
            Log::error('Command: RunPredictionRequests | Failed to dispatch RunPredictionRequestsJob | Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
