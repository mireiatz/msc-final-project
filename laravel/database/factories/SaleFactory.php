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

        return [
            'store_id' => $store->id,
            'date' => $this->getRandomDate(),
            'sale' => 0,
            'cost' => 0,
            'vat' => 0,
            'net_sale' => 0,
            'margin' => 0,
            'currency' => $this->faker->randomElement(['gbp']),
        ];
    }

    public function configure(): self
    {
        return $this->afterCreating(function (Sale $sale) {
            $amount = $this->faker->numberBetween(1, 5);
            $products = $this->getRandomModelInstances(Product::class, $amount);
            $total_sale = 0;
            $total_cost = 0;

            $products->each(function ($product) use ($sale, &$total_sale, &$total_cost) {
                $quantity = $this->faker->numberBetween(1, 10);
                $unitSale = $this->faker->numberBetween(100, 500);
                $totalSale = $quantity * $unitSale;
                $unitCost = $this->faker->numberBetween(50, $unitSale);
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
                    'date' => $sale->date,
                    'quantity' => -1 * $quantity,
                    'stock_balance' => $product->stock_balance + $quantity,
                ]);

                $total_sale += $totalSale;
                $total_cost += $totalCost;
            });

            $vat = (int) round($total_sale * 0.2);
            $net =  $total_sale + $vat;
            $sale->update([
                'sale' => $total_sale,
                'cost' => $total_cost,
                'vat' => $vat,
                'net_sale' => $net,
                'margin' => $total_sale - $total_cost,
            ]);
            $sale->save();
        });
    }
}
