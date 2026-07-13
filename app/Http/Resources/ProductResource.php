<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'brand' => $this->brand,
            'slug' => $this->slug,
            'price' => $this->price,
            'sale_price' => $this->sale_price,
            'final_price' => $this->final_price,
            'has_discount' => $this->has_discount,
            'discount_percent' => $this->discount_percent,
            'is_soldout' => (bool) $this->is_soldout,
            'image' => img_url($this->main_image),
            'category_id' => $this->category_id,
        ];
    }
}
