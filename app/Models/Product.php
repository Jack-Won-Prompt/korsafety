<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'external_no', 'seller_id', 'category_id', 'name', 'slug', 'brand',
        'price', 'sale_price', 'is_soldout', 'main_image',
    ];

    protected $casts = [
        'is_soldout' => 'boolean',
        'price' => 'integer',
        'sale_price' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_category');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort');
    }

    public function galleryImages(): HasMany
    {
        return $this->hasMany(ProductImage::class)->where('type', 'gallery')->orderBy('sort');
    }

    public function detailImages(): HasMany
    {
        return $this->hasMany(ProductImage::class)->where('type', 'detail')->orderBy('sort');
    }

    /** Effective selling price (sale price when it is lower than list price). */
    public function getFinalPriceAttribute(): ?int
    {
        if ($this->sale_price && $this->price && $this->sale_price < $this->price) {
            return $this->sale_price;
        }
        return $this->sale_price ?: $this->price;
    }

    public function getHasDiscountAttribute(): bool
    {
        return $this->price && $this->sale_price && $this->sale_price < $this->price;
    }

    public function getDiscountPercentAttribute(): ?int
    {
        if (! $this->has_discount) return null;
        return (int) round(100 - ($this->sale_price / $this->price * 100));
    }
}
