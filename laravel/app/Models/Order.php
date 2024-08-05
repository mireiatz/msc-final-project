<?php

namespace App\Models;

use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use UsesUuid, HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'provider_id',
        'date',
        'cost',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (Order $order) {

            $order->products()->each(function ($product) use ($order) {
                $quantity = $product->pivot->quantity;

                $order->inventoryTransactions()->create([
                    'store_id' => $order->store_id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'stock_balance' => $product->stock_balance + $quantity,
                ]);
            });
        });

        static::deleting(function ($order) {
            $order->inventoryTransactions()->delete();
        });
    }

    private function calculateStockBalance(Product $product, int $quantity): int
    {
        return $product->stock_balance + $quantity;
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_products')
            ->using(OrderProduct::class)
            ->as('order_products')
            ->withPivot('quantity', 'unit_cost', 'total_cost')
            ->withTimestamps();
    }

    public function inventoryTransactions(): MorphMany
    {
        return $this->morphMany(InventoryTransaction::class, 'parent');
    }
}
