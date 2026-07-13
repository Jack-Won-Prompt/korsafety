<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SellerController extends Controller
{
    /** 판매점 대시보드 — 자사 매출/주문/상품 */
    public function index()
    {
        $seller = Auth::user()->seller;
        $sid = $seller->id;

        $sales = (int) OrderItem::where('seller_id', $sid)->sum('line_total');
        $orderCount = OrderItem::where('seller_id', $sid)->distinct('order_id')->count('order_id');
        $soldQty = (int) OrderItem::where('seller_id', $sid)->sum('qty');

        $stats = [
            'products' => Product::where('seller_id', $sid)->count(),
            'sales' => $sales,
            'orders' => $orderCount,
            'sold_qty' => $soldQty,
        ];

        $rows = OrderItem::query()
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('SUM(line_total) as s'))
            ->where('seller_id', $sid)
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->groupBy('d')->pluck('s', 'd');
        $chart = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $chart[] = ['date' => now()->subDays($i)->format('m/d'), 'value' => (int) ($rows[$d] ?? 0)];
        }

        $bestItems = OrderItem::select('product_name', DB::raw('SUM(qty) as q'), DB::raw('SUM(line_total) as s'))
            ->where('seller_id', $sid)->groupBy('product_name')
            ->orderByDesc('q')->limit(5)->get();

        return view('seller.dashboard', compact('seller', 'stats', 'chart', 'bestItems'));
    }
}
