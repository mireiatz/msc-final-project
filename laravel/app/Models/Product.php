<?php

namespace App\Models;

use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use UsesUuid, HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'provider_id',
        'name',
        'description',
        'unit',
        'amount_per_unit',
        'min_stock_level',
        'max_stock_level',
        'sale',
        'cost',
        'currency',
    ];

    protected $appends = [
        'stock_balance'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function sales()
    {
        return $this->belongsToMany(Sale::class, 'sale_products')
            ->using(SaleProduct::class)
            ->as('sale_products')
            ->withPivot('quantity', 'unit_sale', 'total_sale', 'unit_cost', 'total_cost')
            ->withTimestamps();
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_products')
            ->using(OrderProduct::class)
            ->as('order_products')
            ->withPivot('quantity', 'unit_cost', 'total_cost')
            ->as('order_products')
            ->withTimestamps();
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function getStockBalanceAttribute(): int
    {
        return $this->inventoryTransactions()->sum('quantity');
    }
}
