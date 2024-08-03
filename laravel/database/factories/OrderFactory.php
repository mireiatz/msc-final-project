<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Store;
use App\Traits\RandomModelInstances;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    use RandomModelInstances;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $store =  $this->getRandomModelInstance(Store::class);
        $provider =  $this->getRandomModelInstance(Provider::class);

        return [
            'store_id' => $store->id,
            'provider_id' => $provider->id,
            'date' => Carbon::now()->format('Y-m-d H:i'),
            'cost' => $this->faker->numberBetween(100, 1000),
            'currency' => $this->faker->randomElement(['gbp']),
        ];
    }


    public function configure(): self
    {
        return $this->afterCreating(function (Order $order) {
            $amount = $this->faker->numberBetween(1, 5);
            $products = $this->getRandomModelInstances(Product::class, $amount);

            $products->each(function ($product) use ($order) {
                $quantity = $this->faker->numberBetween(1, 10);
                $unitCost = $this->faker->numberBetween(100, 500);
                $totalCost = $quantity * $unitCost;

                $order->products()->attach($product->id, [
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'currency' => $product->currency,
                ]);

                $order->inventoryTransactions()->create([
                    'store_id' => $order->store_id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'stock_balance' => $product->stock_balance + $quantity,
                ]);
            });
        });
    }
}
