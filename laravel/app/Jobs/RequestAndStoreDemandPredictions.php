<?php

namespace App\Jobs;

use App\Models\Prediction;
use App\Services\ML\MLServiceClientInterface;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RequestAndStoreDemandPredictions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $data;

    protected int $chunkSize = 2000;

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
            // Request predictions from the ML service
            $response = $mlServiceClient->predictDemand($this->data);
            $predictions = json_decode($response['predictions'], true);

            // Chunk the predictions
            $chunks = array_chunk($predictions, $this->chunkSize);
            foreach ($chunks as $chunk) {
                // Upsert records (precaution for the unique constraint for product_id - date)
                Prediction::upsert(
                    $chunk,
                    ['product_id', 'date'], // Unique columns
                    ['value'] // Update on conflict
                );
            }
            Log::info('RequestPredictions job completed | Response: ');
        } catch (Exception $e) {
            Log::error('RequestPredictions job failed | Error sending prediction request: ' . $e->getMessage());
            throw $e;
        }
    }
}
