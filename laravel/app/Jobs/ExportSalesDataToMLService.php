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

class ExportSalesDataToMLService implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $dataType;
    protected string $dataFormat;
    protected ?string $startDate;
    protected ?string $endDate;

    /**
     * Create a new job instance.
     */
    public function __construct(string $dataType, string $dataFormat, ?string $startDate, ?string $endDate)
    {
        $this->dataType = $dataType;
        $this->dataFormat = $dataFormat;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Execute the job.
     *
     * @throws Exception
     */
    public function handle(MLServiceClientInterface $mlServiceClient): void
    {
        try {
            // Get the content for the CSV file
            $csv = $this->createCsvContent();

            // Use the ML service client to export sales data
            $payload = [
                'file' => 'sales_data.csv',
                'content' => $csv,
                'metadata' => [
                    'type' => $this->dataType,
                    'format' => $this->dataFormat,
                ]
            ];

            $response = $mlServiceClient->exportSalesData($payload);

            Log::info('ExportSalesDataToMLService job completed | Response: ', $response);
        } catch (Exception $e) {
            Log::error('ExportSalesDataToMLService job failed | Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create the content to be written into the CSV.
     *
     * @throws Exception
     */
    public function createCsvContent(): string
    {
        // Get all products that are active
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

        // Write the CSV header
        $csvContent .= implode(',', [
                'product_id',
                'product_name',
                'category',
                'quantity',
                'per_item_value',
                'in_stock',
                'date',
            ]) . "\n";

        // Loop through active products and dates, filling in zero sales for missing products
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
