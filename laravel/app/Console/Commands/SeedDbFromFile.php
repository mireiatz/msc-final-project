<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use App\Jobs\SeedProductsFromFile as SeedProductsFromFileJob;
use App\Jobs\SeedSalesFromFile as SeedSalesFromFileJob;
use Illuminate\Support\Facades\Log;

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
    protected $description = 'Seed the database with {type} records from CSV file(s) in {dir}';

    /**
     * Execute the console command.
     * @throws Exception
     */
    public function handle(): void
    {
        // Get all CSV files in the given directory
        $directory = $this->argument('dir');
        $files = glob($directory . '/*.csv');

        // End the command if no files found
        if (empty($files)) {
            $this->error("No CSV files found in the directory: {$directory}");
            return;
        }

        foreach ($files as $file) {
            try {
                switch ($this->argument('type')) {
                    case 'products':
                        SeedProductsFromFileJob::dispatch($file);
                        break;

                    case 'sales':
                        SeedSalesFromFileJob::dispatch($file);
                        break;

                    default:
                        $this->error("Invalid type, use products or sales");
                        return;
                }
            } catch (Exception $e) {
                Log::error('Command: SeedDbFromFile | Failed to dispatch seeding job for file: ' . $file . ' | Error: ' . $e->getMessage());
                throw $e;
            }
        }

        $this->info("Seeding jobs dispatched successfully");
    }
}
