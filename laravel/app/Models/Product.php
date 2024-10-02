<?php

namespace App\Models;

use App\Traits\UsesUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Product extends Model
{
    use UsesUuid, HasFactory, SoftDeletes;

    protected $fillable = [
        'provider_id',
        'category_id',
        'pos_product_id',
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
        'stock_balance',
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
        // Temporary approach for accuracy purposes due to massive data uploads with matching dates/times
        // To be replaced when realistic sales patterns (with varying timestamps) begin
        // Replacement query: $this->inventoryTransactions()->latest('date')->value('stock_balance');
        return $this->inventoryTransactions()->sum('quantity');
    }
}
