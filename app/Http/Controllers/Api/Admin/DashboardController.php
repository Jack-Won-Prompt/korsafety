<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Purchaser;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $hq = Seller::where('is_hq', true)->first();

        $platformSales = (int) OrderItem::sum('line_total');
        $hqSales = (int) OrderItem::where('seller_id', optional($hq)->id)->sum('line_total');
        $orderCount = Order::count();
        $aov = $orderCount ? (int) round(Order::avg('total')) : 0;

        $rows = OrderItem::query()
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('SUM(line_total) as s'))
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->groupBy('d')->pluck('s', 'd');
        $chart = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $chart[] = ['date' => now()->subDays($i)->format('m/d'), 'value' => (int) ($rows[$d] ?? 0)];
        }

        $topSellers = OrderItem::select('seller_id', DB::raw('SUM(line_total) as sales'))
            ->groupBy('seller_id')->orderByDesc('sales')->limit(5)->get()
            ->map(fn ($r) => [
                'name' => optional(Seller::find($r->seller_id))->name ?? '미지정',
                'sales' => (int) $r->sales,
            ]);

        $recent = Order::latest()->limit(6)->get()->map(fn ($o) => [
            'order_no' => $o->order_no,
            'customer_name' => $o->customer_name,
            'total' => $o->total,
            'status' => $o->status,
            'status_label' => $o->status_label,
            'created_at' => optional($o->created_at)->toIso8601String(),
        ]);

        return response()->json([
            'stats' => [
                'platform_sales' => $platformSales,
                'hq_sales' => $hqSales,
                'orders' => $orderCount,
                'aov' => $aov,
                'sellers' => Seller::where('is_hq', false)->count(),
                'products' => Product::count(),
                'pending_sellers' => Seller::where('status', 'pending')->count(),
                'pending_agents' => Agent::where('status', 'pending')->count(),
                'pending_purchasers' => Purchaser::where('status', 'pending')->count(),
            ],
            'chart' => $chart,
            'top_sellers' => $topSellers,
            'recent_orders' => $recent,
        ]);
    }
}
