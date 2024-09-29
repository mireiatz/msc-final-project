<?php

namespace App\Models;

use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleProduct extends Pivot
{
    use UsesUuid, HasFactory, SoftDeletes;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_sale',
        'total_sale',
        'unit_cost',
        'total_cost',
        'currency',
    ];

    protected static function booted(): void
    {
        static::creating(function ($saleProduct) {
            $sale = $saleProduct->sale;
            $product = $saleProduct->product;

            $saleProduct->inventoryTransaction()->create([
                'store_id' => $sale->store_id,
                'product_id' => $product->id,
                'quantity' => -1 * $saleProduct->quantity, // Subtract from stock
                'stock_balance' => $product->stock_balance - $saleProduct->quantity,
                'date' => $sale->date,
            ]);
        });

        static::deleting(function ($saleProduct) {
            $saleProduct->inventoryTransaction()->delete();
        });
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryTransaction(): MorphOne
    {
        return $this->morphOne(InventoryTransaction::class, 'parent');
    }
}
