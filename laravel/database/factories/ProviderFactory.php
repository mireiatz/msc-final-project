<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProviderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' =>  $this->faker->word(),
            'description' => $this->faker->text(),
            'phone' =>  $this->faker->phoneNumber(),
            'email' =>  $this->faker->unique()->safeEmail(),
            'address' => [
                'address_line_1' => $this->faker->streetAddress(),
                'address_line_2' => $this->faker->streetAddress(),
                'city' => $this->faker->city(),
                'postcode' => $this->faker->postcode(),
                'country' => $this->faker->country(),
            ],
            'lead_days' => $this->faker->numberBetween(1, 7),
        ];
    }
}
