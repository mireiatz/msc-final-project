<?php

namespace App\Models;

use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderProduct extends Pivot
{
    use UsesUuid, HasFactory, SoftDeletes;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'currency',
    ];

    protected static function booted(): void
    {
        static::creating(function ($orderProduct) {
            $order = $orderProduct->order;
            $product = $orderProduct->product;

            $orderProduct->inventoryTransaction()->create([
                'store_id' => $order->store_id,
                'product_id' => $product->id,
                'quantity' => $orderProduct->quantity,
                'stock_balance' => $product->stock_balance + $orderProduct->quantity,
                'date' => $order->date,
            ]);
        });

        static::deleting(function ($orderProduct) {
            $orderProduct->inventoryTransaction()->delete();
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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
