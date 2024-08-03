<?php

namespace Database\Factories;

use App\Models\Category;
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

        return [
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'name' =>  $this->faker->word(),
            'description' => $this->faker->text(),
            'unit' => $this->faker->randomElement(['pack', 'litre', 'box']),
            'amount_per_unit' => $this->faker->numberBetween(1, 20),
            'min_stock_level' => $this->faker->numberBetween(1, 100),
            'sale' => $this->faker->numberBetween(10000, 100000),
            'cost' => $this->faker->numberBetween(100, 10000),
            'currency' => $this->faker->randomElement(['gbp']),
        ];
    }
}
