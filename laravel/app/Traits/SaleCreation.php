<?php
namespace App\Traits;

use App\Models\Provider;
use App\Models\Sale;
use App\Models\Store;
use Illuminate\Support\Collection;

trait SaleCreation
{
    private function createSale(Collection $products, array $quantities, string $startDate, string $endDate): Sale
    {
        $store = Store::factory()->create();
        $provider = Provider::factory()->create();
        $total_sale = 0;
        $total_cost = 0;
        $date = now()->between($startDate, $endDate) ? now() : $startDate;

        $sale = Sale::create([
            'store_id' => $store->id,
            'provider_id' => $provider->id,
            'date' => $date,
            'sale' => 0,
            'cost' => 0,
            'vat' => 0,
            'net_sale' => 0,
            'margin' => 0,
            'currency' => 'gbp',
        ]);

        $products->each(function ($product, $index) use ($sale, $quantities, &$total_sale, &$total_cost) {
            $quantity = $quantities[$index];
            $unitSale = $product->sale;
            $totalSale = $quantity * $unitSale;
            $unitCost = $product->cost;
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
                'stock_balance' => $product->stock_balance - $quantity,
            ]);

            $total_sale += $totalSale;
            $total_cost += $totalCost;
        });

        $vat = (int) round($total_sale * 0.2);
        $net = $total_sale + $vat;
        $sale->update([
            'sale' => $total_sale,
            'cost' => $total_cost,
            'vat' => $vat,
            'net_sale' => $net,
            'margin' => $total_sale - $total_cost,
        ]);
        $sale->save();

        return $sale;
    }
}
