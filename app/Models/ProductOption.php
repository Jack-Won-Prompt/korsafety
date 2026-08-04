<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOption extends Model
{
    protected $fillable = ['product_id', 'group_name', 'name', 'extra_price', 'stock', 'is_active', 'sort'];

    protected $casts = [
        'extra_price' => 'integer',
        'stock' => 'integer',
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** 이 옵션을 골랐을 때의 실제 판매가 */
    public function priceFor(Product $product): int
    {
        return max(0, (int) $product->final_price + $this->extra_price);
    }

    /** 목록·선택 상자에 쓰는 표기 (예: 260mm (+3,000원)) */
    public function getLabelAttribute(): string
    {
        $label = $this->name;
        if ($this->extra_price > 0) {
            $label .= ' (+'.number_format($this->extra_price).'원)';
        } elseif ($this->extra_price < 0) {
            $label .= ' ('.number_format($this->extra_price).'원)';
        }

        return $label;
    }
}
