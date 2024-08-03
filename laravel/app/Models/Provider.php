<?php

namespace App\Models;

use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Model
{
    use UsesUuid, HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'phone',
        'email',
        'address',
        'lead_days',
    ];

    protected $casts = [
        'address' => 'array'
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
