<?php

namespace App\Models;

use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryTransaction extends Model
{
    use UsesUuid, HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'parent_id',
        'parent_type',
        'product_id',
        'date',
        'quantity',
        'stock_balance',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function parent(): MorphTo
    {
        return $this->morphTo();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
