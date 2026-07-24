<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Purchaser;
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

    /** 구매 대행자 관리 */
    public function purchasers()
    {
        $accrued = ['paid', 'shipped', 'done'];
        $purchasers = Purchaser::withCount(['buyers', 'orders'])
            ->orderByRaw("FIELD(status,'pending','approved','suspended')")->orderByDesc('id')->get()
            ->map(function ($p) use ($accrued) {
                $base = Order::where('purchaser_id', $p->id);
                $p->cashback_accrued = (int) (clone $base)->whereIn('status', $accrued)->sum('cashback_amount');
                $p->cashback_pending = (int) (clone $base)->whereIn('status', $accrued)->whereNull('cashback_paid_at')->sum('cashback_amount');
                return $p;
            });
        return view('admin.purchasers', compact('purchasers'));
    }

    public function updatePurchaserStatus(Request $request, Purchaser $purchaser)
    {
        $request->validate(['status' => 'required|in:approved,suspended,pending']);
        $purchaser->update(['status' => $request->status]);
        $label = ['approved' => '승인', 'suspended' => '정지', 'pending' => '대기'][$request->status];
        return back()->with('status', "‘{$purchaser->name}’ 상태를 {$label}(으)로 변경했습니다.");
    }

    public function updatePurchaserCashback(Request $request, Purchaser $purchaser)
    {
        $request->validate(['cashback_rate' => 'required|numeric|min:0|max:100']);
        $purchaser->update(['cashback_rate' => $request->cashback_rate]);
        return back()->with('status', "‘{$purchaser->name}’ 캐쉬백 비율을 {$request->cashback_rate}%로 변경했습니다.");
    }

    /** 캐쉬백 정산 */
    public function cashbacks(Request $request)
    {
        $accrued = ['paid', 'shipped', 'done'];
        $filter = $request->query('f', 'pending');

        $q = Order::with(['purchaser', 'buyer'])->whereNotNull('purchaser_id')->whereIn('status', $accrued);
        if ($filter === 'pending') $q->whereNull('cashback_paid_at');
        elseif ($filter === 'paid') $q->whereNotNull('cashback_paid_at');

        $orders = $q->latest('id')->paginate(20)->withQueryString();

        $summary = [
            'accrued' => (int) Order::whereNotNull('purchaser_id')->whereIn('status', $accrued)->sum('cashback_amount'),
            'pending' => (int) Order::whereNotNull('purchaser_id')->whereIn('status', $accrued)->whereNull('cashback_paid_at')->sum('cashback_amount'),
            'paid' => (int) Order::whereNotNull('purchaser_id')->whereNotNull('cashback_paid_at')->sum('cashback_amount'),
        ];

        return view('admin.cashbacks', compact('orders', 'summary', 'filter'));
    }

    public function payCashback(Request $request, Order $order)
    {
        abort_unless($order->purchaser_id && $order->cashback_accrued, 422, '지급 대상 주문이 아닙니다.');
        $order->update(['cashback_paid_at' => now()]);
        return back()->with('status', "주문 {$order->order_no} 캐쉬백을 지급 처리했습니다.");
    }

    /** 로그인 이력 */
    public function loginLogs(Request $request)
    {
        $status = $request->query('status', 'all');   // all | success | failed
        $role = $request->query('role', 'all');
        $q = trim((string) $request->query('q', ''));

        $query = \App\Models\LoginLog::query()->latest('created_at')->latest('id');
        if (in_array($status, ['success', 'failed'], true)) $query->where('status', $status);
        if ($role !== 'all') $query->where('role', $role);
        if ($q !== '') $query->where(fn ($w) => $w->where('email', 'like', "%$q%")->orWhere('name', 'like', "%$q%")->orWhere('ip_address', 'like', "%$q%"));

        $logs = $query->paginate(30)->withQueryString();

        $summary = [
            'total' => \App\Models\LoginLog::count(),
            'success' => \App\Models\LoginLog::where('status', 'success')->count(),
            'failed' => \App\Models\LoginLog::where('status', 'failed')->count(),
            'today' => \App\Models\LoginLog::where('created_at', '>=', now()->startOfDay())->count(),
        ];

        return view('admin.login-logs', compact('logs', 'summary', 'status', 'role', 'q'));
    }

    /** 설정 */
    public function settings()
    {
        $settings = [
            'home_show_categories' => Setting::bool('home_show_categories'),
            'price_display_mode'   => Setting::get('price_display_mode'),
            'maintenance_mode'     => Setting::bool('maintenance_mode'),
            'maintenance_message'  => Setting::get('maintenance_message'),
        ];
        return view('admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate(['maintenance_message' => 'nullable|string|max:200'], [], ['maintenance_message' => '안내 문구']);

        Setting::put('home_show_categories', $request->boolean('home_show_categories') ? '1' : '0');
        Setting::put('price_display_mode', $request->input('price_display_mode') === 'price' ? 'price' : 'ask');
        Setting::put('maintenance_mode', $request->boolean('maintenance_mode') ? '1' : '0');
        Setting::put('maintenance_message', trim((string) $request->input('maintenance_message')) ?: Setting::DEFAULTS['maintenance_message']);
        return redirect()->route('admin.settings')->with('status', '설정이 저장되었습니다.');
    }
}
