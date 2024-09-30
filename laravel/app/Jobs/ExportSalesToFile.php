<?php

namespace App\Jobs;

use App\Models\Sale;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
            $csvContent = $this->createCsvContent();

            // Store the CSV file
            Storage::disk('historical_data_raw')->put($this->filename, $csvContent);

            Log::info('ExportSalesToFile job completed: ' . $this->filename . ' file processed.');
        } catch (Exception $e) {
            Log::error('ExportSalesToFile job failed to export sales to CSV: ' . $this->filename . ' | Error: ' . $e->getMessage());
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
        // Build query to fetch sales data
        $query = Sale::query();

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('date', '>=', $this->startDate);
        } else {
            $today = Carbon::today()->format('Y-m-d');
            $query->whereDate('date', '=', $today);
        }

        $salesData = $query->with(['products.category'])->get();

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

        // Loop through sales data and build CSV rows
        foreach ($salesData as $sale) {
            foreach ($sale->products as $product) {
                $csvContent .= implode(',', [
                        $product->pos_product_id,
                        $product->name,
                        $product->category->name,
                        $product->sale_products->quantity,
                        $product->sale_products->unit_sale/100,
                        $product->stock_balance ?? 0,
                        $sale->date->format('Y-m-d'),
                    ]) . "\n";
            }
        }

        return $csvContent;
    }
}
