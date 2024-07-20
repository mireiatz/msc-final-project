<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

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

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'sales_products')
            ->using(SaleProduct::class)
            ->as('sales_products')
            ->withPivot('quantity', 'unit_sale', 'total_sale', 'unit_cost', 'total_cost')
            ->as('sales_products')
            ->withTimestamps();
    }

    public function inventoryTransactions(): MorphMany
    {
        return $this->morphMany(InventoryTransaction::class, 'parent');
    }
}
