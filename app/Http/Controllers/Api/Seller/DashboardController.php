<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $seller = $request->user()->seller;
        $sid = $seller->id;

        $sales = (int) OrderItem::where('seller_id', $sid)->sum('line_total');
        $orderCount = OrderItem::where('seller_id', $sid)->distinct('order_id')->count('order_id');
        $soldQty = (int) OrderItem::where('seller_id', $sid)->sum('qty');

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
            ->orderByDesc('q')->limit(5)->get()
            ->map(fn ($r) => ['name' => $r->product_name, 'qty' => (int) $r->q, 'sales' => (int) $r->s]);

        return response()->json([
            'seller' => ['name' => $seller->name, 'is_hq' => (bool) $seller->is_hq],
            'stats' => [
                'products' => Product::where('seller_id', $sid)->count(),
                'sales' => $sales,
                'orders' => $orderCount,
                'sold_qty' => $soldQty,
            ],
            'chart' => $chart,
            'best_items' => $bestItems,
        ]);
    }
}
