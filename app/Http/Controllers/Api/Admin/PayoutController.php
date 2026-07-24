<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    private const ACCRUED = ['paid', 'shipped', 'done'];

    /** 커미션 정산 목록 */
    public function commissions(Request $request)
    {
        $filter = $request->query('f', 'pending'); // pending | paid | all

        $q = Order::with(['agent', 'client'])->whereNotNull('agent_id')->whereIn('status', self::ACCRUED);
        if ($filter === 'pending') {
            $q->whereNull('commission_paid_at');
        } elseif ($filter === 'paid') {
            $q->whereNotNull('commission_paid_at');
        }

        $orders = $q->latest('id')->paginate(20);
        $orders->getCollection()->transform(fn ($o) => [
            'id' => $o->id,
            'order_no' => $o->order_no,
            'partner_name' => optional($o->agent)->name,
            'target_name' => optional($o->client)->name,
            'total' => $o->total,
            'amount' => $o->commission_amount,
            'rate' => $o->commission_rate,
            'paid' => $o->commission_paid_at !== null,
            'created_at' => optional($o->created_at)->toIso8601String(),
        ]);

        return response()->json([
            'summary' => $this->summary('commission'),
            'orders' => $orders,
        ]);
    }

    public function payCommission(Request $request, Order $order)
    {
        abort_unless($order->agent_id && $order->commission_accrued, 422, '지급 대상 주문이 아닙니다.');
        $order->update(['commission_paid_at' => now()]);

        return response()->json(['message' => '커미션을 지급 처리했습니다.']);
    }

    /** 캐시백 정산 목록 */
    public function cashbacks(Request $request)
    {
        $filter = $request->query('f', 'pending');

        $q = Order::with(['purchaser', 'buyer'])->whereNotNull('purchaser_id')->whereIn('status', self::ACCRUED);
        if ($filter === 'pending') {
            $q->whereNull('cashback_paid_at');
        } elseif ($filter === 'paid') {
            $q->whereNotNull('cashback_paid_at');
        }

        $orders = $q->latest('id')->paginate(20);
        $orders->getCollection()->transform(fn ($o) => [
            'id' => $o->id,
            'order_no' => $o->order_no,
            'partner_name' => optional($o->purchaser)->name,
            'target_name' => optional($o->buyer)->shop_name,
            'total' => $o->total,
            'amount' => $o->cashback_amount,
            'rate' => $o->cashback_rate,
            'paid' => $o->cashback_paid_at !== null,
            'created_at' => optional($o->created_at)->toIso8601String(),
        ]);

        return response()->json([
            'summary' => $this->summary('cashback'),
            'orders' => $orders,
        ]);
    }

    public function payCashback(Request $request, Order $order)
    {
        abort_unless($order->purchaser_id && $order->cashback_accrued, 422, '지급 대상 주문이 아닙니다.');
        $order->update(['cashback_paid_at' => now()]);

        return response()->json(['message' => '캐시백을 지급 처리했습니다.']);
    }

    private function summary(string $type): array
    {
        $col = $type === 'commission' ? 'commission_amount' : 'cashback_amount';
        $paidCol = $type === 'commission' ? 'commission_paid_at' : 'cashback_paid_at';
        $fk = $type === 'commission' ? 'agent_id' : 'purchaser_id';

        return [
            'accrued' => (int) Order::whereNotNull($fk)->whereIn('status', self::ACCRUED)->sum($col),
            'pending' => (int) Order::whereNotNull($fk)->whereIn('status', self::ACCRUED)->whereNull($paidCol)->sum($col),
            'paid' => (int) Order::whereNotNull($fk)->whereNotNull($paidCol)->sum($col),
        ];
    }
}
