<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Buyer extends Model
{
    protected $fillable = [
        'purchaser_id', 'shop_name', 'name', 'business_no', 'phone', 'address', 'memo',
    ];

    public function purchaser(): BelongsTo
    {
        return $this->belongsTo(Purchaser::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
