<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOption extends Model
{
    // 새 칸을 여기 적지 않으면 Laravel 이 저장할 때 조용히 버린다.
    // 실제로 price·partner_price 를 표와 화면에만 더하고 이 줄을 빠뜨려,
    // 입력칸은 보이는데 값이 저장되지 않는 일이 있었다.
    protected $fillable = ['product_id', 'group_name', 'name', 'extra_price',
        'price', 'partner_price', 'stock', 'is_active', 'sort'];

    protected $casts = [
        'extra_price' => 'integer',
        // 비워 두면 상품 본체 가격을 따른다는 뜻이라, 0 으로 바뀌지 않게 null 을 지킨다
        'price' => 'integer',
        'partner_price' => 'integer',
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
