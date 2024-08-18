<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Provider;
use App\Traits\RandomModelInstances;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    use RandomModelInstances;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $provider =  $this->getRandomModelInstance(Provider::class);
        $category =  $this->getRandomModelInstance(Category::class);

        $productName = $this->faker->unique()->word();
        while (Product::where('name', $productName)
            ->exists()) {
            $productName = $this->faker->unique()->word();
        }

        return [
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'name' => $productName,
            'description' => $this->faker->text(),
            'unit' => $this->faker->randomElement(['pack', 'litre', 'box']),
            'amount_per_unit' => $this->faker->numberBetween(1, 20),
            'min_stock_level' => $this->faker->numberBetween(1, 100),
            'max_stock_level' => $this->faker->numberBetween(100, 200),
            'sale' => $this->faker->numberBetween(10000, 100000),
            'cost' => $this->faker->numberBetween(100, 10000),
            'currency' => $this->faker->randomElement(['gbp']),
        ];
    }
}
