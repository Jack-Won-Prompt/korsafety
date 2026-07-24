<?php

namespace App\Http\Controllers\Api\Purchaser;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $purchaser = $request->user()->purchaser;
        $pid = $purchaser->id;

        $accrued = ['paid', 'shipped', 'done'];
        $base = Order::where('purchaser_id', $pid);

        $salesPaid = (int) (clone $base)->whereIn('status', $accrued)->sum('total');
        $cashbackAccrued = (int) (clone $base)->whereIn('status', $accrued)->sum('cashback_amount');
        $cashbackPaid = (int) (clone $base)->whereNotNull('cashback_paid_at')->sum('cashback_amount');
        $cashbackPending = (int) (clone $base)->whereIn('status', $accrued)
            ->whereNull('cashback_paid_at')->sum('cashback_amount');

        $rows = (clone $base)->whereIn('status', $accrued)
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('SUM(cashback_amount) as s'))
            ->groupBy('d')->pluck('s', 'd');

        $chart = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $chart[] = ['date' => now()->subDays($i)->format('m/d'), 'value' => (int) ($rows[$d] ?? 0)];
        }

        $recent = (clone $base)->with('buyer')->latest('id')->limit(5)->get()->map(fn ($o) => [
            'id' => $o->id,
            'order_no' => $o->order_no,
            'buyer_name' => optional($o->buyer)->shop_name,
            'total' => $o->total,
            'status' => $o->status,
            'status_label' => $o->status_label,
            'created_at' => optional($o->created_at)->toIso8601String(),
        ]);

        return response()->json([
            'purchaser' => [
                'name' => $purchaser->name,
                'cashback_rate' => $purchaser->cashback_rate,
            ],
            'stats' => [
                'buyers_count' => Buyer::where('purchaser_id', $pid)->count(),
                'orders_count' => (clone $base)->count(),
                'total_sales' => $salesPaid,
                'cashback_accrued' => $cashbackAccrued,
                'cashback_paid' => $cashbackPaid,
                'cashback_pending' => $cashbackPending,
            ],
            'chart' => $chart,
            'recent_orders' => $recent,
        ]);
    }
}
