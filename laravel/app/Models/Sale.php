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

    protected $casts = [
        'date' => 'datetime',
    ];

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
            ->withTimestamps();
    }
}
