<?php

namespace App\Traits;

use App\Models\Order;
use App\Models\Provider;
use App\Models\Store;
use Illuminate\Support\Collection;

trait OrderCreation
{
    private function createOrder(Collection $products, array $quantities, string $startDate, string $endDate): Order
    {
        $store = Store::factory()->create();
        $provider = Provider::factory()->create();
        $total_cost = 0;
        $date = now()->between($startDate, $endDate) ? now() : $startDate;

        $order = Order::create([
            'store_id' => $store->id,
            'provider_id' => $provider->id,
            'date' => $date,
            'cost' => 0,
            'currency' => 'gbp',
        ]);

        $products->each(function ($product, $index) use ($order, $quantities, &$total_cost) {
            $quantity = $quantities[$index];
            $unitCost = $product->cost;
            $totalCost = $quantity * $unitCost;

            $order->products()->attach($product->id, [
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'currency' => $product->currency,
            ]);

            // Inventory transactions are created in an event hook in the model

            $total_cost += $totalCost;
            $product->update(['stock_balance' => $product->stock_balance + $quantity]);
        });

        $order->update([
            'cost' => $total_cost,
        ]);
        $order->save();

        return $order;
    }
}
