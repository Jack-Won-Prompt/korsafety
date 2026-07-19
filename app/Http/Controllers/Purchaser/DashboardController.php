<?php

namespace App\Http\Controllers\Purchaser;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $purchaser = Auth::user()->purchaser;
        $pid = $purchaser->id;

        $accruedStatuses = ['paid', 'shipped', 'done'];
        $base = Order::where('purchaser_id', $pid);

        $salesPaid = (int) (clone $base)->whereIn('status', $accruedStatuses)->sum('total');
        $cashbackAccrued = (int) (clone $base)->whereIn('status', $accruedStatuses)->sum('cashback_amount');
        $cashbackPaid = (int) (clone $base)->whereNotNull('cashback_paid_at')->sum('cashback_amount');
        $cashbackPending = (int) (clone $base)->whereIn('status', $accruedStatuses)
            ->whereNull('cashback_paid_at')->sum('cashback_amount');

        $stats = [
            'orders' => (clone $base)->count(),
            'buyers' => Buyer::where('purchaser_id', $pid)->count(),
            'sales' => $salesPaid,
            'cashback_accrued' => $cashbackAccrued,
            'cashback_pending' => $cashbackPending,
            'cashback_paid' => $cashbackPaid,
            'rate' => $purchaser->cashback_rate,
        ];

        $rows = (clone $base)->whereIn('status', $accruedStatuses)
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('SUM(cashback_amount) as s'))
            ->groupBy('d')->pluck('s', 'd');
        $chart = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $chart[] = ['date' => now()->subDays($i)->format('m/d'), 'value' => (int) ($rows[$d] ?? 0)];
        }

        $recent = (clone $base)->with('buyer')->latest('id')->limit(8)->get();

        return view('purchaser.dashboard', compact('purchaser', 'stats', 'chart', 'recent'));
    }
}
