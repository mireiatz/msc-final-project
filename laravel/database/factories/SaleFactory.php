<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Traits\RandomDate;
use App\Traits\RandomModelInstances;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    use RandomModelInstances, RandomDate;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $store =  $this->getRandomModelInstance(Store::class);
        $sale = $this->faker->numberBetween(10000, 100000);
        $cost = $this->faker->numberBetween(100, 10000);
        $vat = (int) round($sale * 0.2);
        $net =  $sale + $vat;
        $margin = $sale - $cost;

        return [
            'store_id' => $store->id,
            'date' => $this->getRandomDate(),
            'sale' => $sale,
            'cost' => $cost,
            'vat' => $vat,
            'net_sale' => $net,
            'margin' => $margin,
            'currency' => $this->faker->randomElement(['gbp']),
        ];
    }

    public function configure(): self
    {
        return $this->afterCreating(function (Sale $sale) {
            $amount = $this->faker->numberBetween(1, 5);
            $products = $this->getRandomModelInstances(Product::class, $amount);

            foreach ($products as $product) {
                $quantity = $this->faker->numberBetween(1, 10);
                $unitSale = $this->faker->numberBetween(100, 500);
                $totalSale = $quantity * $unitSale;
                $unitCost = $this->faker->numberBetween(50, 250);
                $totalCost = $quantity * $unitCost;

                $sale->products()->attach($product->id, [
                    'quantity' => $quantity,
                    'unit_sale' => $unitSale,
                    'total_sale' => $totalSale,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'currency' => $product->currency,
                ]);

                $sale->inventoryTransactions()->create([
                    'store_id' => $sale->store_id,
                    'product_id' => $product->id,
                    'quantity' => -1 * $quantity,
                    'stock_balance' => $product->stock_balance + $quantity,
                ]);
            }
        });
    }
}
