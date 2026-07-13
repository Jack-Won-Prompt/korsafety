<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $agent = $request->user()->agent;

        $orders = Order::where('agent_id', $agent->id);

        $accruedStatuses = ['paid', 'shipped', 'done'];

        $totalSales = (clone $orders)->whereIn('status', $accruedStatuses)->sum('total');
        $commissionAccrued = (clone $orders)->whereIn('status', $accruedStatuses)->sum('commission_amount');
        $commissionPaid = (clone $orders)->whereNotNull('commission_paid_at')->sum('commission_amount');

        $recent = (clone $orders)->with('client')->latest('id')->limit(5)->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'order_no' => $o->order_no,
                'client_name' => optional($o->client)->name,
                'total' => $o->total,
                'status' => $o->status,
                'status_label' => $o->status_label,
                'created_at' => optional($o->created_at)->toIso8601String(),
            ]);

        return response()->json([
            'agent' => [
                'name' => $agent->name,
                'commission_rate' => $agent->commission_rate,
            ],
            'stats' => [
                'clients_count' => $agent->clients()->count(),
                'orders_count' => (clone $orders)->count(),
                'total_sales' => (int) $totalSales,
                'commission_accrued' => (int) $commissionAccrued,
                'commission_paid' => (int) $commissionPaid,
                'commission_unpaid' => (int) ($commissionAccrued - $commissionPaid),
            ],
            'recent_orders' => $recent,
        ]);
    }
}
