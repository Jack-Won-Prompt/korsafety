<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'external_no', 'seller_id', 'category_id', 'name', 'slug', 'sku', 'brand',
        'price', 'cost_price', 'sale_price', 'stock', 'safety_stock', 'track_stock',
        'is_soldout', 'is_active', 'sort', 'main_image', 'description',
    ];

    protected $casts = [
        'is_soldout' => 'boolean',
        'is_active' => 'boolean',
        'track_stock' => 'boolean',
        'price' => 'integer',
        'cost_price' => 'integer',
        'sale_price' => 'integer',
        'stock' => 'integer',
        'safety_stock' => 'integer',
        'sort' => 'integer',
    ];

    /** 쇼핑몰에 실제로 노출되는 상품 */
    public function scopeVisible($query)
    {
        return $query->where('products.is_active', true);
    }

    /** 재고 관리를 켠 상품 중 안전재고 이하로 떨어진 것 */
    public function scopeLowStock($query)
    {
        return $query->where('track_stock', true)
            ->whereColumn('stock', '<=', 'safety_stock')
            ->where('stock', '>', 0);
    }

    /** 재고 관리를 켰는데 재고가 바닥난 상품 */
    public function scopeOutOfStock($query)
    {
        return $query->where('track_stock', true)->where('stock', '<=', 0);
    }

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

    /** 판매가 대비 매입가 마진율 */
    public function getMarginPercentAttribute(): ?int
    {
        $sell = $this->final_price;
        if (! $sell || ! $this->cost_price) return null;
        return (int) round(($sell - $this->cost_price) / $sell * 100);
    }

    /** 재고 경고 단계: untracked | out | low | none */
    public function getStockLevelAttribute(): string
    {
        if (! $this->track_stock) return 'untracked';
        if ($this->stock <= 0) return 'out';
        if ($this->safety_stock > 0 && $this->stock <= $this->safety_stock) return 'low';
        return 'none';
    }
}
