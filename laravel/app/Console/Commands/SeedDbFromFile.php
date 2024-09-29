<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SeedProductsFromFile as SeedProductsFromFileJob;
use App\Jobs\SeedSalesFromFile as SeedSalesFromFileJob;

class SeedDbFromFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:seed-from-file {dir} {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with {type} records from CSV file(s) in: {dir}';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Get all CSV files in the given directory
        $directory = $this->argument('dir');

        $files = glob($directory . '/*.csv');

        if (empty($files)) {
            $this->error("No CSV files found in the directory: {$directory}");
            return;
        }

        foreach ($files as $file) {
            switch ($this->argument('type')) {
                case 'products':
                    SeedProductsFromFileJob::dispatch($file);
                    break;

                case 'sales':
                    SeedSalesFromFileJob::dispatch($file);
                    break;

                default:
                    $this->error("Invalid type. Use 'products' or 'sales'.");
                    return;
            }
        }

        $this->info("All CSV files dispatched.");
    }
}
