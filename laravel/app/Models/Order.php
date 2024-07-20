<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'provider_id',
        'date',
        'cost',
    ];

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
        return $this->belongsToMany(Product::class, 'orders_products')
            ->using(OrderProduct::class)
            ->as('orders_products')
            ->withPivot('quantity', 'unit_cost', 'total_cost')
            ->withTimestamps();
    }

    public function inventoryTransactions(): MorphMany
    {
        return $this->morphMany(InventoryTransaction::class, 'parent');
    }
}
