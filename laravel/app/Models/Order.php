<?php

namespace App\Models;

use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use UsesUuid, HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'provider_id',
        'date',
        'cost',
        'currency',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

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
}
