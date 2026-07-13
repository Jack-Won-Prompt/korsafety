<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
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
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'gallery' => $this->whenLoaded('images', fn () => $this->images
                ->where('type', 'gallery')->values()
                ->map(fn ($i) => img_url($i->path))),
            'details' => $this->whenLoaded('images', fn () => $this->images
                ->where('type', 'detail')->values()
                ->map(fn ($i) => img_url($i->path))),
        ];
    }
}
