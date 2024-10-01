<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExportSalesToFile implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $filename;
    protected ?string $startDate;
    protected ?string $endDate;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath, ?string $startDate, ?string $endDate)
    {
        $this->filename = $filePath;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Execute the job.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        try {
            // Get the content for the CSV file
            $csv = $this->createCsvContent();

            // Send the CSV file to Flask via HTTP request
            $response = Http::attach(
                'file',
                $csv,
                'sales_data.csv'
            )->post('http://ml:5002/preprocess-historical-data');

            if ($response->successful()) {
                Log::info("CSV successfully sent to ML service.");
            } else {
                Log::error("Failed to send CSV to ML service: " . $response->body());
            }
        } catch (Exception $e) {
            Log::error('ExportSalesToFile job failed: ' . $this->filename . ' | Error: ' . $e->getMessage());
            throw new Exception($e->getMessage());

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
            $query->whereDate('date', '=', Carbon::today()->format('Y-m-d'));
        }

        $salesData = $query->with(['products.category'])->get();

        // Map product sales by product_id and date
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
        $dates = $this->generateDateRange();
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

    /**
     * Helper function to generate date range
     *
     * @return array
     */
    protected function generateDateRange(): array
    {
        $startDate = Carbon::parse($this->startDate ?? Carbon::today());
        $endDate = Carbon::parse($this->endDate ?? Carbon::today());
        $dates = [];

        while ($startDate->lte($endDate)) {
            $dates[] = $startDate->format('Y-m-d');
            $startDate->addDay();
        }

        return $dates;
    }
}
