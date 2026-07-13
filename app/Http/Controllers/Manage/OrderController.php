<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /** 현재 스토어 상품이 포함된 주문내역 */
    public function index()
    {
        $sid = Auth::user()->seller_id;
        $items = OrderItem::with('order')
            ->where('seller_id', $sid)
            ->latest('id')
            ->paginate(20);

        return view('manage.orders', compact('items'));
    }
}
