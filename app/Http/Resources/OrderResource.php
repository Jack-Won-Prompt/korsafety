<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_no' => $this->order_no,
            'total' => $this->total,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'receiver_name' => $this->receiver_name,
            'customer_phone' => $this->customer_phone,
            'postcode' => $this->postcode,
            'address1' => $this->address1,
            'address2' => $this->address2,
            'delivery_memo' => $this->delivery_memo,
            'paid_at' => optional($this->paid_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($it) => [
                'id' => $it->id,
                'product_id' => $it->product_id,
                'product_name' => $it->product_name,
                'price' => $it->price,
                'qty' => $it->qty,
                'line_total' => $it->line_total,
                'image' => img_url(optional($it->product)->main_image),
            ])),
        ];
    }
}
