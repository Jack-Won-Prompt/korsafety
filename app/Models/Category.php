<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    protected $fillable = ['slug', 'name', 'sort'];
    public $timestamps = true;

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_category');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
