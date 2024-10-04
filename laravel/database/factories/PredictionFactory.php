<?php

namespace Database\Factories;

use App\Models\Product;
use App\Traits\RandomModelInstances;
use Illuminate\Database\Eloquent\Factories\Factory;

class PredictionFactory extends Factory
{
    use RandomModelInstances;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $product = $this->getRandomModelInstance(Product::class);

        return [
            'product_id' => $product->id,
            'date' => $this->faker->date(),
            'value' => $this->faker->numberBetween(1, 100),
        ];
    }
}
