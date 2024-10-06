<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Sale;
use App\Services\ML\MLServiceClientInterface;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExportDailySalesDataToMLService implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?string $startDate;
    protected ?string $endDate;

    public function __construct(?string $startDate, ?string $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Execute the job to export historical sales data (in daily format) to the ML microservice.
     *
     * @throws Exception
     */
    public function handle(MLServiceClientInterface $mlServiceClient): void
    {
        try {
            // Get the content for the CSV file
            $csvContent = $this->createCsvContent();

            // Write the CSV content to a temporary file
            $csvFilePath = tempnam(sys_get_temp_dir(), 'sales_data_') . '.csv';
            file_put_contents($csvFilePath, $csvContent);

            // Define the payload
            $payload = [
                'file' => $csvFilePath, // The path to the file
                'metadata' => [
                    'type' => 'historical', // Only type currently
                    'format' => 'daily', // To indicate the correct preprocessing approach
                ]
            ];

            // Use the ML Client to send the sales data to the ML microservice
            $response = $mlServiceClient->exportSalesData($payload);

            // Clean up temporary file
            unlink($csvFilePath);

            Log::info('ExportSalesDataToMLService job completed | Response: ', $response);
        } catch (Exception $e) {
            Log::error('ExportSalesDataToMLService job failed | Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create the content to be written into the CSV.
     *
     * @return string
     */
    public function createCsvContent(): string
    {
        // Get all products that are active (sales in the last 3 months)
        $activeProducts = Product::whereHas('sales', function ($query) {
            $query->where('date', '>=', Carbon::now()->subMonths(3));
        })->get();

        // Build query to fetch sales data based on dates
        $query = Sale::query();

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('date', '>=', $this->startDate);
        } else {
            // If dates haven't been defined, assume it's today's sync
            $query->whereDate('date', '=', Carbon::today()->format('Y-m-d'));
        }

        $salesData = $query->with(['products.category'])->get();

        // Map product sales by product ID and date
        $salesByProductAndDate = [];
        foreach ($salesData as $sale) {
            foreach ($sale->products as $product) {
                $salesByProductAndDate[$product->product_id][$sale->date->format('Y-m-d')] = $product;
            }
        }

        $csvContent = '';

        // CSV header
        $csvContent .= implode(',', [
                'product_id',
                'product_name',
                'category',
                'quantity',
                'per_item_value',
                'in_stock',
                'date',
            ]) . "\n";

        // Loop through active products and dates
        $dates = generateDateRange($this->startDate, $this->endDate);
        foreach ($activeProducts as $product) {
            foreach ($dates as $date) {
                $saleProduct = $salesByProductAndDate[$product->product_id][$date] ?? null;

                // Fill zero sales if no sales for this product on the current date
                $csvContent .= implode(',', [
                        $product->pos_product_id,
                        $product->name,
                        $product->category->name,
                        $saleProduct ? $saleProduct->sale_products->quantity : 0,
                        $saleProduct ? $saleProduct->sale_products->unit_sale / 100 : 0,
                        $saleProduct ? $saleProduct->stock_balance ?? 0 : 0,
                        $date,
                    ]) . "\n";
            }
        }

        return $csvContent;
    }
}
