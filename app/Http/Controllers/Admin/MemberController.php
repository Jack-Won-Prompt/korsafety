<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\Order;
use App\Models\PartnerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

/** 회원 관리 — 가입 회원 조회 · 비밀번호 재설정 안내 · 이용 정지 (본사 전용) */
class MemberController extends Controller
{
    /** 역할 구분 (목록 탭) */
    public const ROLES = [
        'customer' => '일반 회원',
        'partner' => '협력사 회원',
        'hq_admin' => '본사',
        'seller' => '판매점',
        'agent' => '영업 협력사',
        'purchaser' => '구매 대행자',
    ];

    public function index(Request $request)
    {
        $role = $request->query('role', 'customer');
        if ($role !== 'all' && ! isset(self::ROLES[$role])) {
            $role = 'customer';
        }
        $state = $request->query('state');          // active | suspended
        $q = trim((string) $request->query('q', ''));
        $from = $request->query('from');
        $to = $request->query('to');

        $query = User::query()
            ->with('partnerProfile')
            ->withCount('orders')
            ->withSum('orders', 'total')
            ->addSelect(['last_login_at' => LoginLog::select('created_at')
                ->whereColumn('user_id', 'users.id')->where('status', 'success')
                ->orderByDesc('created_at')->limit(1)]);

        if ($role !== 'all') {
            $query->where('role', $role);
        }
        match ($state) {
            'suspended' => $query->whereNotNull('suspended_at'),
            'active' => $query->whereNull('suspended_at'),
            default => null,
        };
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%"));
        }
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        $members = $query->latest('id')->paginate(30)->withQueryString();

        $byRole = User::selectRaw('role, COUNT(*) c')->groupBy('role')->pluck('c', 'role');
        $stats = [
            'all' => (int) $byRole->sum(),
            'customer' => (int) ($byRole['customer'] ?? 0),
            'today' => User::whereDate('created_at', today())->count(),
            'week' => User::where('created_at', '>=', today()->subDays(6))->count(),
            'suspended' => User::whereNotNull('suspended_at')->count(),
            'partner_pending' => PartnerProfile::where('status', 'pending')->count(),
        ];
        foreach (array_keys(self::ROLES) as $r) {
            $stats[$r] = (int) ($byRole[$r] ?? 0);
        }

        return view('admin.members.index', compact('members', 'stats', 'role', 'state', 'q', 'from', 'to'));
    }

    /** 협력사 회원 승인 · 반려 — 승인해야 협력사 할인가가 적용된다 */
    public function partnerStatus(Request $request, PartnerProfile $profile)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,approved,rejected',
            'reject_reason' => 'nullable|string|max:300',
        ], [], ['status' => '승인 상태', 'reject_reason' => '반려 사유']);

        $profile->update([
            'status' => $data['status'],
            'approved_at' => $data['status'] === 'approved' ? now() : null,
            'approved_by' => $data['status'] === 'approved' ? auth()->id() : null,
            'reject_reason' => $data['status'] === 'rejected' ? ($data['reject_reason'] ?? null) : null,
        ]);

        return back()->with('status', $profile->company_name.' 협력사를 "'.PartnerProfile::STATUSES[$data['status']].'" 상태로 바꿨습니다.');
    }

    public function show(User $member)
    {
        $member->load('partnerProfile.approver');
        $orders = Order::where('user_id', $member->id)->latest('id')->limit(10)->get();
        $logins = LoginLog::where('user_id', $member->id)->orWhere('email', $member->email)
            ->latest('created_at')->limit(10)->get();
        $summary = [
            'orders' => Order::where('user_id', $member->id)->count(),
            'amount' => (int) Order::where('user_id', $member->id)->sum('total'),
            'last_order_at' => Order::where('user_id', $member->id)->max('created_at'),
        ];

        return view('admin.members.show', compact('member', 'orders', 'logins', 'summary'));
    }

    /** 비밀번호 재설정 링크를 회원 이메일로 보낸다 (관리자가 비밀번호를 직접 보거나 바꾸지 않는다) */
    public function sendReset(User $member)
    {
        $status = Password::sendResetLink(['email' => $member->email]);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', $member->email.' 로 비밀번호 재설정 링크를 보냈습니다.');
        }

        return back()->with('error', '재설정 링크를 보내지 못했습니다. 메일 설정을 확인해 주세요.');
    }

    /** 이용 정지 / 해제 — 정지된 계정은 웹·앱 모두 로그인할 수 없다 */
    public function toggleSuspend(Request $request, User $member)
    {
        if ($member->id === auth()->id()) {
            return back()->with('error', '본인 계정은 정지할 수 없습니다.');
        }

        if ($member->suspended_at) {
            $member->update(['suspended_at' => null]);

            return back()->with('status', $member->name.' 회원의 이용 정지를 해제했습니다.');
        }

        $member->update(['suspended_at' => now()]);

        return back()->with('status', $member->name.' 회원을 이용 정지했습니다. 이제 로그인할 수 없습니다.');
    }

    /** 회원 삭제 — 주문 이력이 있으면 기록 보존을 위해 막는다 */
    public function destroy(Request $request, User $member)
    {
        if ($member->id === auth()->id()) {
            return back()->with('error', '본인 계정은 삭제할 수 없습니다.');
        }
        if ($member->role !== 'customer') {
            return back()->with('error', '일반 회원만 삭제할 수 있습니다. 관리·판매점 계정은 이용 정지를 사용해 주세요.');
        }
        if (Order::where('user_id', $member->id)->exists()) {
            return back()->with('error', '주문 이력이 있는 회원은 삭제할 수 없습니다. 이용 정지를 사용해 주세요.');
        }

        $name = $member->name;
        $member->delete();

        return redirect()->route('admin.members')->with('status', $name.' 회원을 삭제했습니다.');
    }
}
