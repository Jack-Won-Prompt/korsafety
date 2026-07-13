<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /** 본사 대시보드 — 플랫폼 전체 + 본사 직영 매출/주문 */
    public function index()
    {
        $hq = Seller::where('is_hq', true)->first();

        $platformSales = (int) OrderItem::sum('line_total');
        $hqSales = (int) OrderItem::where('seller_id', optional($hq)->id)->sum('line_total');
        $orderCount = Order::count();
        $aov = $orderCount ? (int) round(Order::avg('total')) : 0;

        $stats = [
            'sellers' => Seller::where('is_hq', false)->count(),
            'pending' => Seller::where('status', 'pending')->count(),
            'products' => Product::count(),
            'hq_products' => Product::where('seller_id', optional($hq)->id)->count(),
            'platform_sales' => $platformSales,
            'hq_sales' => $hqSales,
            'orders' => $orderCount,
            'aov' => $aov,
        ];

        // 최근 14일 일별 매출 (플랫폼)
        $chart = $this->dailySeries(null);

        // 판매점별 매출 TOP
        $topSellers = OrderItem::select('seller_id', DB::raw('SUM(line_total) as sales'))
            ->groupBy('seller_id')->orderByDesc('sales')->limit(5)->get()
            ->map(function ($r) {
                $r->seller = Seller::find($r->seller_id);
                return $r;
            });

        $recentOrders = Order::latest()->limit(8)->get();

        return view('admin.dashboard', compact('stats', 'chart', 'topSellers', 'recentOrders'));
    }

    /** 최근 14일 일별 매출 시계열 (seller 지정 시 해당 스토어) */
    private function dailySeries(?int $sellerId): array
    {
        $q = OrderItem::query()
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('SUM(line_total) as s'))
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->groupBy('d');
        if ($sellerId) $q->where('seller_id', $sellerId);
        $rows = $q->pluck('s', 'd');

        $out = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $out[] = ['date' => now()->subDays($i)->format('m/d'), 'value' => (int) ($rows[$d] ?? 0)];
        }
        return $out;
    }

    /** 판매점 관리 */
    public function sellers()
    {
        $sellers = Seller::where('is_hq', false)
            ->withCount('products')
            ->orderByRaw("FIELD(status,'pending','approved','suspended')")
            ->orderByDesc('id')
            ->get()
            ->map(function ($s) {
                $s->sales = (int) OrderItem::where('seller_id', $s->id)->sum('line_total');
                return $s;
            });

        return view('admin.sellers', compact('sellers'));
    }

    public function updateSellerStatus(Request $request, Seller $seller)
    {
        $request->validate(['status' => 'required|in:approved,suspended,pending']);
        if ($seller->is_hq) {
            return back()->withErrors(['status' => '본사 직영 스토어는 변경할 수 없습니다.']);
        }
        $seller->update(['status' => $request->status]);
        $label = ['approved' => '승인', 'suspended' => '정지', 'pending' => '대기'][$request->status];
        return back()->with('status', "‘{$seller->name}’ 상태를 {$label}(으)로 변경했습니다.");
    }

    /** 협력사 관리 */
    public function agents()
    {
        $accrued = ['paid', 'shipped', 'done'];
        $agents = Agent::withCount(['clients', 'orders'])
            ->orderByRaw("FIELD(status,'pending','approved','suspended')")->orderByDesc('id')->get()
            ->map(function ($a) use ($accrued) {
                $base = Order::where('agent_id', $a->id);
                $a->commission_accrued = (int) (clone $base)->whereIn('status', $accrued)->sum('commission_amount');
                $a->commission_pending = (int) (clone $base)->whereIn('status', $accrued)->whereNull('commission_paid_at')->sum('commission_amount');
                return $a;
            });
        return view('admin.agents', compact('agents'));
    }

    public function updateAgentStatus(Request $request, Agent $agent)
    {
        $request->validate(['status' => 'required|in:approved,suspended,pending']);
        $agent->update(['status' => $request->status]);
        $label = ['approved' => '승인', 'suspended' => '정지', 'pending' => '대기'][$request->status];
        return back()->with('status', "‘{$agent->name}’ 상태를 {$label}(으)로 변경했습니다.");
    }

    public function updateAgentCommission(Request $request, Agent $agent)
    {
        $request->validate(['commission_rate' => 'required|numeric|min:0|max:100']);
        $agent->update(['commission_rate' => $request->commission_rate]);
        return back()->with('status', "‘{$agent->name}’ 수수료율을 {$request->commission_rate}%로 변경했습니다.");
    }

    /** 커미션 정산 */
    public function commissions(Request $request)
    {
        $accrued = ['paid', 'shipped', 'done'];
        $filter = $request->query('f', 'pending'); // pending | paid | all

        $q = Order::with(['agent', 'client'])->whereNotNull('agent_id')->whereIn('status', $accrued);
        if ($filter === 'pending') $q->whereNull('commission_paid_at');
        elseif ($filter === 'paid') $q->whereNotNull('commission_paid_at');

        $orders = $q->latest('id')->paginate(20)->withQueryString();

        $summary = [
            'accrued' => (int) Order::whereNotNull('agent_id')->whereIn('status', $accrued)->sum('commission_amount'),
            'pending' => (int) Order::whereNotNull('agent_id')->whereIn('status', $accrued)->whereNull('commission_paid_at')->sum('commission_amount'),
            'paid' => (int) Order::whereNotNull('agent_id')->whereNotNull('commission_paid_at')->sum('commission_amount'),
        ];

        return view('admin.commissions', compact('orders', 'summary', 'filter'));
    }

    public function payCommission(Request $request, Order $order)
    {
        abort_unless($order->agent_id && $order->commission_accrued, 422, '지급 대상 주문이 아닙니다.');
        $order->update(['commission_paid_at' => now()]);
        return back()->with('status', "주문 {$order->order_no} 커미션을 지급 처리했습니다.");
    }

    /** 설정 */
    public function settings()
    {
        $settings = ['home_show_categories' => Setting::bool('home_show_categories')];
        return view('admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        Setting::put('home_show_categories', $request->boolean('home_show_categories') ? '1' : '0');
        return redirect()->route('admin.settings')->with('status', '설정이 저장되었습니다.');
    }
}
