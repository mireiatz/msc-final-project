<?php

namespace Database\Seeders;

use App\Models\Provider;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            StoreSeeder::class,
            ProviderSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
        ]);

        for ($i = 0; $i < 10; $i++) {
            $this->call(SaleSeeder::class);
            $this->call(OrderSeeder::class);
        }
    }
}
