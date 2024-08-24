<?php

namespace App\Models;

use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use UsesUuid, HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'date',
        'sale',
        'cost',
        'vat',
        'net_sale',
        'margin',
        'currency',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (Sale $sale) {

            $sale->products()->each(function ($product) use ($sale) {
                $quantity = -1 * $product->pivot->quantity;

                $sale->inventoryTransactions()->create([
                    'store_id' => $sale->store_id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'stock_balance' => $product->stock_balance + $quantity,
                ]);
            });
        });

        static::deleting(function ($sale) {
            $sale->inventoryTransactions()->delete();
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'sale_products')
            ->using(SaleProduct::class)
            ->as('sale_products')
            ->withPivot('quantity', 'unit_sale', 'total_sale', 'unit_cost', 'total_cost')
            ->as('sale_products')
            ->withTimestamps();
    }

    public function inventoryTransactions(): MorphMany
    {
        return $this->morphMany(InventoryTransaction::class, 'parent');
    }
}
