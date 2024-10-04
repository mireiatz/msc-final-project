<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\Product;
use App\Models\Provider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use League\Csv\Exception;
use Throwable;

class SeedProductsFromFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $file;

    /**
     * Create a new job instance.
     * @param string $file
     */
    public function __construct(string $file)
    {
        $this->file = $file;
    }

    /**
     * Execute the job.
     * @throws Exception
     * @throws Throwable
     */
    public function handle(): void
    {
        // Parse the CSV file
        $rows = parseCsvFile($this->file);

        foreach ($rows as $row) {
            try {
                if($row['quantity'] <= 0){
                    Log::warning("SeedProductsFromFile job: Invalid quantity for product: {$row['product_name']} in category {$row['category']}. Quantity is 0.");
                    continue;
                }
                $keyword = $this->mapCategory($row['category']);
                $details = $this->getProductDetailsByCategory($keyword);
                $providerId = $details['provider'];
                $unit = $details['unit'];

                $sale = ($row['value'] / $row['quantity']) * 100; // Saved as cents
                $cost = round($sale * (rand(50, 80) / 100)); // Random cost 50%-80% of sale

                # Create new products
                Product::updateOrCreate(
                    [
                        'name' => $row['product_name'],
                        'category_id' => $this->getCategoryId($row['category']),
                    ],
                    [
                        'provider_id' => $providerId,
                        'category_id' => $this->getCategoryId($row['category']),
                        'pos_product_id' => strval($row['product_id']),
                        'name' => $row['product_name'],
                        'description' => $row['product_name'],
                        'unit' => $unit,
                        'amount_per_unit' => rand(1, 100),
                        'min_stock_level' => rand(10, 50),
                        'max_stock_level' => rand(100, 500),
                        'sale' => $sale,
                        'cost' => $cost,
                        'currency' => 'gbp',
                    ]);
            } catch (Throwable $e) {
                Log::error('SeedProductsFromFile job: Failed to create product for row: ' . json_encode($row) . ' | Error: ' . $e->getMessage());
                throw $e;
            }
        }
        Log::info('SeedProductsFromFile job completed: ' . $this->file . ' processed successfully');
    }

    /**
     * Get the category ID by name, creating the category if it doesn't exist.
     *
     * @param string $name
     * @return string
     */
    protected function getCategoryId(string $name): string
    {
        $category = Category::firstOrCreate(['name' => $name]);

        return $category->id;
    }

    /**
     * Map the category name into a keyword.
     *
     * @param string $category
     * @return string
     */
    protected function mapCategory(string $category): string
    {
        // Standardise the name
        $category = strtolower($category);
        $category = str_replace(['&', '/', ' '], '_', $category);

        // Return the appropriate keyword
        switch (true) {
            case str_contains($category, 'snack') || str_contains($category, 'ice_cream'):
                return 'snack';
            case str_contains($category, 'breakfast') || str_contains($category, 'cereal'):
                return 'breakfast';
            case str_contains($category, 'drink') || str_contains($category, 'beverage') || str_contains($category, 'juice'):
                return 'drinks';
            case str_contains($category, 'sauce') || str_contains($category, 'spread') || str_contains($category, 'oil') || str_contains($category, 'herb') || str_contains($category, 'spice') || str_contains($category, 'jam'):
                return 'condiments';
            case str_contains($category, 'baby'):
                return 'baby';
            case str_contains($category, 'household') || str_contains($category, 'foil') || str_contains($category, 'tights') || str_contains($category, 'paper') || str_contains($category, 'batteries'):
                return 'household';
            case str_contains($category, 'cleaning') || str_contains($category, 'washing') || str_contains($category, 'toiletries'):
                return 'cleaning';
            case str_contains($category, 'cake') || str_contains($category, 'baking') || str_contains($category, 'bread') || str_contains($category, 'biscuit') || str_contains($category, 'dessert'):
                return 'baking';
            case str_contains($category, 'pet'):
                return 'pet';
            case str_contains($category, 'milk'):
                return 'milk';
            case str_contains($category, 'medical'):
                return 'medical';
            case str_contains($category, 'soups') || str_contains($category, 'meat') || str_contains($category, 'fish') || str_contains($category, 'fruit') || str_contains($category, 'veg'):
                return 'food';
            case str_contains($category, 'grocery') || str_contains($category, 'meal'):
                return 'grocery';
            default:
                return 'miscellaneous';
        }
    }

    /**
     * Get product details (provider and unit) based on the category.
     *
     * @param string $keyword
     * @return array
     */
    protected function getProductDetailsByCategory(string $keyword): array
    {
        # Get the appropriate provider
        $provider = Provider::where('description', 'like', "%{$keyword}%")->first();
        if (!$provider) {
            $provider = Provider::inRandomOrder()->first();
        }

        # Get random appropriate units
        switch ($keyword) {
            case 'snack':
                $unit = rand(0, 1) ? 'pack' : 'bag';
                break;
            case 'grocery':
            case 'breakfast':
            case 'miscellaneous':
                $unit = rand(0, 1) ? 'pack' : 'box';
                break;
            case 'milk':
            case 'drinks':
                $unit = rand(0, 1) ? 'liter' : 'ml';
                break;
            case 'condiments':
                $unit = rand(0, 1) ? 'jar' : 'bottle';
                break;
            case 'baby':
            case 'household':
            case 'cleaning':
            case 'medical':
                $unit = 'pack';
                break;
            case 'pet':
            case 'baking':
                $unit = rand(0, 1) ? 'kg' : 'grams';
                break;
            default:
                $unit = 'unit';
                break;
        }

        return [
            'provider' => $provider->id,
            'unit' => $unit,
        ];
    }
}
