<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Purchaser;
use App\Models\Seller;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    private const ACCRUED = ['paid', 'shipped', 'done'];

    /** 판매점 목록 */
    public function sellers()
    {
        $sellers = Seller::where('is_hq', false)
            ->withCount('products')
            ->orderByRaw("FIELD(status,'pending','approved','suspended')")
            ->orderByDesc('id')->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'status' => $s->status,
                'status_label' => $s->status_label,
                'owner_name' => $s->owner_name,
                'phone' => $s->phone,
                'commission_rate' => $s->commission_rate,
                'products_count' => $s->products_count,
                'sales' => (int) OrderItem::where('seller_id', $s->id)->sum('line_total'),
            ]);

        return response()->json(['data' => $sellers]);
    }

    public function updateSellerStatus(Request $request, Seller $seller)
    {
        $data = $request->validate(['status' => 'required|in:approved,suspended,pending']);
        if ($seller->is_hq) {
            return response()->json(['message' => '본사 직영 스토어는 변경할 수 없습니다.'], 422);
        }
        $seller->update(['status' => $data['status']]);

        return response()->json(['message' => '상태를 변경했습니다.']);
    }

    /** 협력사 목록 */
    public function agents()
    {
        $agents = Agent::withCount(['clients', 'orders'])
            ->orderByRaw("FIELD(status,'pending','approved','suspended')")
            ->orderByDesc('id')->get()
            ->map(function ($a) {
                $base = Order::where('agent_id', $a->id);

                return [
                    'id' => $a->id,
                    'name' => $a->name,
                    'status' => $a->status,
                    'status_label' => $a->status_label,
                    'owner_name' => $a->owner_name,
                    'phone' => $a->phone,
                    'commission_rate' => $a->commission_rate,
                    'clients_count' => $a->clients_count,
                    'orders_count' => $a->orders_count,
                    'commission_pending' => (int) (clone $base)->whereIn('status', self::ACCRUED)
                        ->whereNull('commission_paid_at')->sum('commission_amount'),
                ];
            });

        return response()->json(['data' => $agents]);
    }

    public function updateAgentStatus(Request $request, Agent $agent)
    {
        $data = $request->validate(['status' => 'required|in:approved,suspended,pending']);
        $agent->update(['status' => $data['status']]);

        return response()->json(['message' => '상태를 변경했습니다.']);
    }

    public function updateAgentRate(Request $request, Agent $agent)
    {
        $data = $request->validate(['commission_rate' => 'required|numeric|min:0|max:100']);
        $agent->update(['commission_rate' => $data['commission_rate']]);

        return response()->json(['message' => '수수료율을 변경했습니다.']);
    }

    /** 구매처 목록 */
    public function purchasers()
    {
        $purchasers = Purchaser::withCount(['buyers', 'orders'])
            ->orderByRaw("FIELD(status,'pending','approved','suspended')")
            ->orderByDesc('id')->get()
            ->map(function ($p) {
                $base = Order::where('purchaser_id', $p->id);

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'status' => $p->status,
                    'status_label' => $p->status_label,
                    'owner_name' => $p->owner_name,
                    'phone' => $p->phone,
                    'cashback_rate' => $p->cashback_rate,
                    'buyers_count' => $p->buyers_count,
                    'orders_count' => $p->orders_count,
                    'cashback_pending' => (int) (clone $base)->whereIn('status', self::ACCRUED)
                        ->whereNull('cashback_paid_at')->sum('cashback_amount'),
                ];
            });

        return response()->json(['data' => $purchasers]);
    }

    public function updatePurchaserStatus(Request $request, Purchaser $purchaser)
    {
        $data = $request->validate(['status' => 'required|in:approved,suspended,pending']);
        $purchaser->update(['status' => $data['status']]);

        return response()->json(['message' => '상태를 변경했습니다.']);
    }

    public function updatePurchaserRate(Request $request, Purchaser $purchaser)
    {
        $data = $request->validate(['cashback_rate' => 'required|numeric|min:0|max:100']);
        $purchaser->update(['cashback_rate' => $data['cashback_rate']]);

        return response()->json(['message' => '캐시백율을 변경했습니다.']);
    }
}
