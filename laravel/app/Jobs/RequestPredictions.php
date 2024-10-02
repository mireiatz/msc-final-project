<?php

namespace App\Jobs;

use App\Services\ML\MLServiceClientInterface;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RequestPredictions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $data;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @throws Exception
     */
    public function handle(MLServiceClientInterface $mlServiceClient): void
    {
        try {
            // Use the ML service client to predict demand
            $response = $mlServiceClient->predictDemand($this->data);

            Log::info('RequestPredictions job completed | Response: ', $response);
        } catch (Exception $e) {
            Log::error('RequestPredictions job failed | Error sending prediction request: ' . $e->getMessage());
            throw $e;
        }
    }
}
