<?php

namespace App\Jobs;

use App\Models\Prediction;
use App\Models\Product;
use App\Services\ML\MLServiceClientInterface;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RunDemandPredictionRequests implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $daysToPredict;
    protected int $historicalDays;
    public int $chunkSize = 500;

    /**
     * Create a new job instance.
     */
    public function __construct(?int $daysToPredict = null, ?int $historicalDays = null) {
        $this->daysToPredict = $daysToPredict ?? 35;
        $this->historicalDays = $historicalDays ?? 35;
    }

    /**
     * Execute the job to request demand predictions from the ML servIce and save them in the DB.
     *
     * @throws Exception
     */
    public function handle(MLServiceClientInterface $mlServiceClient): void
    {
        Log::info('Forecast demand started');

        try {
            // Get the content for the CSV file
            $csvContent = $this->generateCsvContent();

            // Generate all dates from today to the defined days to predict
            $today = Carbon::today();
            $dates = generateDateRange($today, $today->copy()->addDays($this->daysToPredict));
            array_pop($dates); // Exclude last date

            // Write the CSV content to a temporary file
            $csvFilePath = tempnam(sys_get_temp_dir(), 'prediction_data_') . '.csv';
            file_put_contents($csvFilePath, $csvContent);

            // Define the payload
            $payload = [
                'file' => $csvFilePath, // The path to the file
                'metadata' => [
                    'type' => 'prediction', // As a precaution
                    'prediction_dates' => $dates, // Dates for prediction sent as metadata
                ]
            ];

            // Use the ML Client to request predictions from the ML microservice
            $response = $mlServiceClient->predictDemand($payload);

            // Get the predictions from the response
            $predictions = json_decode($response['predictions'], true);

            // Chunk the predictions
            $chunks = array_chunk($predictions, $this->chunkSize);
            foreach ($chunks as $chunk) {
                // Upsert records (unique product_id - date constraint)
                Prediction::upsert(
                    $chunk,
                    ['product_id', 'date'], // Unique columns
                    ['value'] // Update on conflict
                );
            }

            // Clean up temporary file
            unlink($csvFilePath);

            Log::info('RunPredictionRequests job completed');
        } catch (Exception $e) {
            Log::error('RunPredictionRequests job failed | Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate CSV content for the prediction request.
     *
     * @return string
     */
    public function generateCsvContent(): string
    {
        $csvContent = '';

        // Get all products that are active (sales in the last 3 months)
        $activeProducts = Product::whereHas('sales', function ($query) {
            $query->where('date', '>=', Carbon::now()->subMonths(3));
        })->get();

        // CSV Header
        $csvContent .= implode(',', [
                'source_product_id',
                'product_name',
                'category',
                'per_item_value',
                'in_stock',
                'date',
                'quantity',
            ]) . "\n";

        // Loop through active products and get their sales history
        foreach ($activeProducts as $product) {
            $historicalSales = $this->getSalesHistory($product);

            // Make a record for each sale
            foreach ($historicalSales as $salesRecord) {
                $csvContent .= implode(',', [
                        $product->id,
                        $product->name,
                        $product->category->name,
                        $product->sale / 100,
                        $product->stock_balance,
                        $salesRecord['date'],
                        $salesRecord['quantity']
                    ]) . "\n";
            }
        }

        return $csvContent;
    }

    /**
     * Get historical sales for a given product.
     *
     * @param Product $product
     * @return array
     */
    protected function getSalesHistory(Product $product): array
    {
        $today = Carbon::today();
        $start_date = $today->copy()->subDays($this->historicalDays);
        $end_date = $today->copy()->subDay();

        // Generate all dates for the historical range
        $dates = array_map(function ($date) {
            return Carbon::parse($date)->format('Y-m-d'); //Only dates, not times
        }, generateDateRange($start_date, $end_date));

        // Get all sales for the product in the date range
        $sales = $product->inventoryTransactions()
            ->whereBetween(DB::raw('DATE(date)'), [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')]) // Only dates, not times
            ->where('parent_type', 'App\Models\SaleProduct')
            ->selectRaw('DATE(date) as sale_date, SUM(ABS(quantity)) as total_quantity') // Sales transactions are negative, get their absolute values
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get()
            ->keyBy('sale_date');

        // Build sales history
        return array_map(function($date) use ($sales) {
            $sale = $sales->get(Carbon::parse($date)->format('Y-m-d'));
            return [
                'date' => $date,
                'quantity' => $sale ? $sale->total_quantity : 0,  // 0 if no sale for that date
            ];
        }, $dates);
    }
}
