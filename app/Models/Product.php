<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** 삭제한 상품은 휴지통에 남겨 되살릴 수 있게 한다 */
    use SoftDeletes;

    protected $fillable = [
        'external_no', 'seller_id', 'category_id', 'name', 'slug', 'sku', 'product_code', 'brand',
        'price', 'cost_price', 'sale_price', 'partner_price', 'stock', 'safety_stock', 'track_stock',
        'is_soldout', 'is_active', 'is_best', 'best_sort', 'sort', 'main_image', 'description',
    ];

    protected $casts = [
        'is_soldout' => 'boolean',
        'is_active' => 'boolean',
        'is_best' => 'boolean',
        'best_sort' => 'integer',
        'track_stock' => 'boolean',
        'price' => 'integer',
        'cost_price' => 'integer',
        'sale_price' => 'integer',
        'partner_price' => 'integer',
        'stock' => 'integer',
        'safety_stock' => 'integer',
        'sort' => 'integer',
    ];

    /** 쇼핑몰에 실제로 노출되는 상품 */
    public function scopeVisible($query)
    {
        return $query->where('products.is_active', true);
    }

    /**
     * 미노출 카테고리에만 속한 상품은 쇼핑몰에서 숨긴다.
     * 카테고리를 하나도 지정하지 않은 상품은 그대로 보여준다(분류 전 상품까지 사라지지 않도록).
     */
    public function scopeInVisibleCategory($query)
    {
        $ids = Category::visibleIds();

        return $query->where(function ($w) use ($ids) {
            $w->whereDoesntHave('categories')
                ->orWhereHas('categories', fn ($c) => $c->whereIn('categories.id', $ids));
        });
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

    /**
     * 검색 친화 URL — /product/3270-크린가드-s40-위생화단화
     * 앞의 숫자가 실제 식별자라 상품명이 바뀌거나 겹쳐도 주소가 깨지지 않는다.
     */
    public function getRouteKey(): string
    {
        $slug = $this->urlSlug();

        return $slug === '' ? (string) $this->id : $this->id.'-'.$slug;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if ($field) {
            return parent::resolveRouteBinding($value, $field);
        }

        // '3270-크린가드-s40'에서 앞의 숫자만 떼어 조회한다
        return $this->newQuery()->find((int) $value);
    }

    /** URL에 쓸 슬러그 — Str::slug는 한글을 통째로 지우므로 직접 만든다 */
    public function urlSlug(): string
    {
        $s = mb_strtolower(trim((string) $this->name));
        $s = preg_replace('/[^가-힣ㄱ-ㅎa-z0-9]+/u', '-', $s);

        return mb_substr(trim($s, '-'), 0, 60);
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

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('sort')->orderBy('id');
    }

    public function activeOptions(): HasMany
    {
        return $this->options()->where('is_active', true);
    }

    /**
     * 실제 판매 가격.
     * 승인된 협력사 회원이 보고 있고 그 상품에 협력사 할인가가 있으면 그 가격으로 판다.
     * 장바구니·주문·앱 모두 이 값을 쓰므로 한 곳만 바꾸면 전체에 반영된다.
     */
    public function getFinalPriceAttribute(): ?int
    {
        if ($this->partner_price && $this->partnerPriceApplies()) {
            return $this->partner_price;
        }

        return $this->listFinalPrice();
    }

    /** 협력사 할인가를 뺀, 일반 회원 기준 판매가 */
    public function listFinalPrice(): ?int
    {
        if ($this->sale_price && $this->price && $this->sale_price < $this->price) {
            return $this->sale_price;
        }

        return $this->sale_price ?: $this->price;
    }

    /** 지금 보고 있는 사람이 승인된 협력사 회원인가 */
    public function partnerPriceApplies(): bool
    {
        static $cached = null;

        if ($cached === null) {
            $user = auth()->user();
            $cached = (bool) ($user && method_exists($user, 'isApprovedPartner') && $user->isApprovedPartner());
        }

        return $cached;
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
