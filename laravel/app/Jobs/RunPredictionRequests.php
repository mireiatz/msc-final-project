<?php

namespace App\Jobs;

use App\Models\Product;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RunPredictionRequests implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $daysToPredict;
    protected int $historicalDays;

    /**
     * Create a new job instance.
     */
    public function __construct(?int $daysToPredict = null, ?int $historicalDays = null) {
        $this->daysToPredict = $daysToPredict ?? 30;
        $this->historicalDays = $historicalDays ?? 30;
    }

    /**
     * Execute the job.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        // Collect prediction data
        $payload = $this->collectPredictionData();

        try {
            // Chunk products data
            $chunks = array_chunk($payload['products'], 1000);

            // Dispatch jobs per chunk including the prediction dates
            foreach ($chunks as $chunk) {
                $chunkedPayload = [
                    'prediction_dates' => $payload['prediction_dates'],
                    'products' => $chunk,
                ];
                dispatch(new RequestPredictions($chunkedPayload));
            }

            Log::info('RunPredictionRequests job completed: dispatched ' . count($chunks) . ' chunk(s) to the ML service');
        } catch (Exception $e) {
            Log::error('RunPredictionRequests job failed | Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Collect and format the data required for prediction.
     *
     * @return array
     */
    protected function collectPredictionData(): array
    {
        // Get all active products
        $activeProducts = Product::whereHas('sales', function ($query) {
            $query->where('date', '>=', Carbon::now()->subMonths(3));
        })->with('category')->get();

        // Generate all dates from today to the defined days to predict
        $today = Carbon::today();
        $dates = generateDateRange($today, $today->copy()->addDays($this->daysToPredict));

        // Build payload with info per product
        $payload = [
            'prediction_dates' => $dates,
            'products' => []
        ];

        foreach ($activeProducts as $product) {
            $payload['products'][] = [
                'details' => [
                    'source_product_id' => $product->id,
                    'product_name' => $product->name,
                    'category' => $product->category->name,
                    'per_item_value' => $product->sale / 100,
                    'in_stock' => $product->stock_balance,
                ],
                'historical_sales' => $this->getSalesHistory($product),
             ];
        }
        return $payload;
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
