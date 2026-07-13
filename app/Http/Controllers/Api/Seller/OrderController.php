<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /** 자사 상품이 포함된 주문 항목 목록 */
    public function index(Request $request)
    {
        $sid = $request->user()->seller_id;

        $items = OrderItem::where('seller_id', $sid)
            ->with('order')
            ->latest('id')
            ->paginate(20);

        $items->getCollection()->transform(fn ($it) => [
            'id' => $it->id,
            'order_no' => optional($it->order)->order_no,
            'product_name' => $it->product_name,
            'price' => $it->price,
            'qty' => $it->qty,
            'line_total' => $it->line_total,
            'order_status' => optional($it->order)->status,
            'order_status_label' => optional($it->order)->status_label,
            'created_at' => optional($it->created_at)->toIso8601String(),
        ]);

        return response()->json($items);
    }
}
