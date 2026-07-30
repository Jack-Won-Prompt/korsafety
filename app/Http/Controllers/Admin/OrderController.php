<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /** 전체 주문 목록 (HQ) */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $query = Order::withCount('items')->with(['statements', 'taxInvoices'])->latest('id');
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('order_no', 'like', "%$q%")
                    ->orWhere('customer_name', 'like', "%$q%")
                    ->orWhere('customer_phone', 'like', "%$q%");
            });
        }
        if ($status) {
            $query->where('status', $status);
        }
        $orders = $query->paginate(20)->withQueryString();

        return view('admin.orders.index', compact('orders', 'q', 'status'));
    }

    /** 주문 상세 — 거래명세서 · 세금계산서 액션 */
    public function show(Order $order)
    {
        $order->load('items', 'user', 'statements.issuer', 'taxInvoices');

        return view('admin.orders.show', compact('order'));
    }
}
