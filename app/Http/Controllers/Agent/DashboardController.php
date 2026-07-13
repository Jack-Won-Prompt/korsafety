<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $agent = Auth::user()->agent;
        $aid = $agent->id;

        $accruedStatuses = ['paid', 'shipped', 'done'];
        $base = Order::where('agent_id', $aid);

        $salesPaid = (int) (clone $base)->whereIn('status', $accruedStatuses)->sum('total');
        $commissionAccrued = (int) (clone $base)->whereIn('status', $accruedStatuses)->sum('commission_amount');
        $commissionPaid = (int) (clone $base)->whereNotNull('commission_paid_at')->sum('commission_amount');
        $commissionPending = (int) (clone $base)->whereIn('status', $accruedStatuses)
            ->whereNull('commission_paid_at')->sum('commission_amount');

        $stats = [
            'orders' => (clone $base)->count(),
            'clients' => Client::where('agent_id', $aid)->count(),
            'sales' => $salesPaid,
            'commission_accrued' => $commissionAccrued,
            'commission_pending' => $commissionPending,
            'commission_paid' => $commissionPaid,
            'rate' => $agent->commission_rate,
        ];

        $rows = (clone $base)->whereIn('status', $accruedStatuses)
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('SUM(commission_amount) as s'))
            ->groupBy('d')->pluck('s', 'd');
        $chart = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $chart[] = ['date' => now()->subDays($i)->format('m/d'), 'value' => (int) ($rows[$d] ?? 0)];
        }

        $recent = (clone $base)->with('client')->latest('id')->limit(8)->get();

        return view('agent.dashboard', compact('agent', 'stats', 'chart', 'recent'));
    }
}
