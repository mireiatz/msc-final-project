<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'provider_id',
        'name',
        'description',
        'unit',
        'amount_per_unit',
        'min_stock_level',
        'sale',
        'cost',
        'currency',
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
        return $this->belongsToMany(Sale::class, 'sales_products')
            ->using(SaleProduct::class)
            ->as('sales_products')
            ->withPivot('quantity', 'unit_sale', 'total_sale', 'unit_cost', 'total_cost')
            ->withTimestamps();
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'orders_products')
            ->using(OrderProduct::class)
            ->as('order_products')
            ->withPivot('quantity', 'unit_cost', 'total_cost')
            ->as('orders_products')
            ->withTimestamps();
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }
}
